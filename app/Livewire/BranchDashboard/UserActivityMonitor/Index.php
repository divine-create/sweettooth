<?php

namespace App\Livewire\BranchDashboard\UserActivityMonitor;

use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\ActivityDescriptionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('User Activity Monitor')]
class Index extends Component
{
    use WithPagination;

    public string $tab = 'overview';

    // Raw log filters (inside disclosure on overview tab)
    public string $feedSearch    = '';
    public string $feedModule    = '';
    public string $feedEventType = '';
    public string $feedUserId    = '';
    public string $feedDateFrom  = '';
    public string $feedDateTo    = '';

    // Staff Activity tab
    public string $userTabUserId = '';

    // Who's Active tab
    public int $onlineMinutes = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        abort_unless(is_super_admin(), 403);
    }

    public function updatingTab(string $value): void
    {
        $this->resetPage();

        if ($value === 'overview') {
            $this->dispatch('render-overview-chart');
        }
    }

    public function updatingFeedSearch(): void    { $this->resetPage(); }
    public function updatingFeedModule(): void    { $this->resetPage(); }
    public function updatingFeedEventType(): void { $this->resetPage(); }
    public function updatingFeedUserId(): void    { $this->resetPage(); }
    public function updatingFeedDateFrom(): void  { $this->resetPage(); }
    public function updatingFeedDateTo(): void    { $this->resetPage(); }

    public function clearFeedFilters(): void
    {
        $this->feedSearch    = '';
        $this->feedModule    = '';
        $this->feedEventType = '';
        $this->feedUserId    = '';
        $this->feedDateFrom  = '';
        $this->feedDateTo    = '';
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        abort_unless(is_super_admin(), 403);

        // KPIs — always computed (fast indexed counts)
        $todayLogins       = UserActivityLog::today()->where('event_type', 'login')->count();
        $onlineCount       = $this->onlineUsersQuery()->distinct('user_id')->count('user_id');
        $failedToday       = UserActivityLog::today()->where('event_type', 'failed_login')->count();
        $totalActionsToday = UserActivityLog::today()->where('event_type', 'component_action')->count();

        $topModuleRaw = UserActivityLog::today()
            ->whereNotNull('module')
            ->selectRaw('module, COUNT(*) as cnt')
            ->groupBy('module')
            ->orderByDesc('cnt')
            ->value('module');
        $topModule = ActivityDescriptionService::moduleLabel($topModuleRaw);

        // Alert badge — cheap query, runs every render
        $alertBadgeCount = UserActivityLog::today()
            ->where('event_type', 'failed_login')
            ->selectRaw('ip_address, COUNT(*) as c')
            ->groupBy('ip_address')
            ->having('c', '>=', 3)
            ->count()
            + UserActivityLog::today()
                ->where('event_type', 'login')
                ->whereRaw('HOUR(created_at) < 5')
                ->count();

        $allUsers   = User::orderBy('name')->get(['id', 'name', 'email']);
        $modules    = UserActivityLog::distinct('module')->whereNotNull('module')->pluck('module')->sort()->values();
        $eventTypes = ['login', 'logout', 'failed_login', 'page_view', 'component_action'];

        return view('livewire.branch-dashboard.user-activity-monitor.index', [
            'todayLogins'       => $todayLogins,
            'onlineCount'       => $onlineCount,
            'failedToday'       => $failedToday,
            'topModule'         => $topModule,
            'totalActionsToday' => $totalActionsToday,
            'alertBadgeCount'   => $alertBadgeCount,
            'allUsers'          => $allUsers,
            'modules'           => $modules,
            'eventTypes'        => $eventTypes,
            // Tab-specific (lazy)
            'chartData'       => $this->tab === 'overview' ? $this->getChartData()        : null,
            'moduleBreakdown' => $this->tab === 'overview' ? $this->getModuleBreakdown()  : null,
            'recentActivity'  => $this->tab === 'overview' ? $this->getRecentActivity()   : null,
            'feedLogs'        => $this->tab === 'overview' ? $this->feedQuery()->paginate(20) : null,
            'onlineUsers'     => $this->tab === 'active'   ? $this->onlineUsersData()      : null,
            'timelineLogs'    => $this->tab === 'user'     ? $this->timelineQuery()->get() : null,
            'activitySummary' => $this->tab === 'user' && $this->userTabUserId
                ? $this->getActivitySummary($this->userTabUserId)
                : null,
            'alerts' => $this->tab === 'alerts' ? $this->getAlerts() : null,
        ]);
    }

    // ── Overview ──────────────────────────────────────────────────────────────

    protected function getChartData(): array
    {
        $rows = UserActivityLog::today()
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $labels = [];
        $counts = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $counts[] = $rows[$h] ?? 0;
        }

        return compact('labels', 'counts');
    }

    protected function getModuleBreakdown(): array
    {
        return UserActivityLog::today()
            ->whereNotNull('module')
            ->selectRaw('module, COUNT(*) as count')
            ->groupBy('module')
            ->orderByDesc('count')
            ->limit(10)
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($r) => [$r->module => $r->count])
            ->toArray();
    }

    protected function getRecentActivity(): Collection
    {
        return UserActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($log) => [
                'sentence'   => ActivityDescriptionService::sentence($log),
                'event_type' => $log->event_type,
                'created_at' => $log->created_at,
            ]);
    }

    // ── Raw log feed (inside disclosure) ─────────────────────────────────────

    protected function feedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $q = UserActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($this->feedSearch) {
            $q->where(function ($sub) {
                $sub->where('action_name', 'like', "%{$this->feedSearch}%")
                    ->orWhere('route_name', 'like', "%{$this->feedSearch}%")
                    ->orWhere('url', 'like', "%{$this->feedSearch}%");
            });
        }

        if ($this->feedModule)    { $q->where('module', $this->feedModule); }
        if ($this->feedEventType) { $q->where('event_type', $this->feedEventType); }
        if ($this->feedUserId)    { $q->where('user_id', $this->feedUserId); }

        if ($this->feedDateFrom) {
            $q->where('created_at', '>=', Carbon::parse($this->feedDateFrom)->startOfDay());
        }
        if ($this->feedDateTo) {
            $q->where('created_at', '<=', Carbon::parse($this->feedDateTo)->endOfDay());
        }

        return $q;
    }

    // ── Staff Activity (timeline) ─────────────────────────────────────────────

    /** @return array{login_sentence:string,module_sentence:string|null,total_sentence:string} */
    protected function getActivitySummary(string $userId): array
    {
        $todayLogs = UserActivityLog::today()
            ->where('user_id', $userId)
            ->get(['event_type', 'module', 'action_name', 'created_at']);

        $loginCount = $todayLogs->where('event_type', 'login')->count();
        $firstLogin = $todayLogs->where('event_type', 'login')->sortBy('created_at')->first();
        $actions    = $todayLogs->where('event_type', 'component_action');

        $topModuleRaw = $todayLogs->whereNotNull('module')
            ->groupBy('module')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        $topModule = ActivityDescriptionService::moduleLabel($topModuleRaw);

        $user      = User::find($userId);
        $firstName = $user ? explode(' ', $user->name)[0] : 'This person';

        $loginSentence = match ($loginCount) {
            0       => "{$firstName} has not logged in today.",
            1       => "{$firstName} logged in once today"
                . ($firstLogin ? ' at ' . $firstLogin->created_at->format('g:ia') : '') . '.',
            default => "{$firstName} logged in {$loginCount} times today.",
        };

        $moduleSentence = $topModuleRaw
            ? "{$firstName} spent most time in the {$topModule} section "
                . "({$actions->where('module', $topModuleRaw)->count()} actions)."
            : null;

        return [
            'login_sentence'  => $loginSentence,
            'module_sentence' => $moduleSentence,
            'total_sentence'  => "{$actions->count()} total actions today.",
        ];
    }

    protected function timelineQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $q = UserActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(500);

        if ($this->userTabUserId) {
            $q->where('user_id', $this->userTabUserId);
        }

        return $q;
    }

    // ── Who's Active ─────────────────────────────────────────────────────────

    protected function onlineUsersQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return UserActivityLog::online($this->onlineMinutes);
    }

    protected function onlineUsersData(): Collection
    {
        $rows = UserActivityLog::online($this->onlineMinutes)
            ->whereNotNull('user_id')
            ->orderBy('created_at', 'desc')
            ->get(['user_id', 'module', 'event_type', 'action_name', 'route_name', 'url', 'ip_address', 'created_at']);

        return $rows->groupBy('user_id')->map(function ($entries) {
            /** @var UserActivityLog $latest */
            $latest = $entries->first();

            return (object) [
                'user_id'        => $latest->user_id,
                'module'         => $latest->module,
                'event_type'     => $latest->event_type,
                'action_name'    => $latest->action_name,
                'route_name'     => $latest->route_name,
                'url'            => $latest->url,
                'ip_address'     => $latest->ip_address,
                'created_at'     => $latest->created_at,
                'activity_count' => $entries->count(),
            ];
        })->values();
    }

    // ── Alerts ────────────────────────────────────────────────────────────────

    /** @return list<object{severity:string,title:string,description:string,timestamp:string}> */
    protected function getAlerts(): array
    {
        $alerts = [];
        $now    = Carbon::now();

        // 1. Multiple failed logins from same IP (3+ in last hour)
        $failedRows = UserActivityLog::where('event_type', 'failed_login')
            ->where('created_at', '>=', $now->copy()->subHour())
            ->whereNotNull('ip_address')
            ->selectRaw('ip_address, COUNT(*) as c, MAX(created_at) as last_at')
            ->groupBy('ip_address')
            ->having('c', '>=', 3)
            ->toBase()
            ->get();

        foreach ($failedRows as $row) {
            $alerts[] = (object) [
                'severity'    => 'red',
                'title'       => 'Multiple Failed Login Attempts',
                'description' => "{$row->c} incorrect password attempts from the same location in the last hour.",
                'timestamp'   => Carbon::parse($row->last_at)->diffForHumans(),
            ];
        }

        // 2. After-hours logins (before 5am today)
        $earlyLogins = UserActivityLog::today()
            ->where('event_type', 'login')
            ->whereRaw('HOUR(created_at) < 5')
            ->get(['user_id', 'created_at']);

        if ($earlyLogins->isNotEmpty()) {
            $userNames = User::whereIn('id', $earlyLogins->pluck('user_id'))->pluck('name', 'id');
            foreach ($earlyLogins as $log) {
                $name = $userNames[$log->user_id] ?? 'A user';
                $alerts[] = (object) [
                    'severity'    => 'amber',
                    'title'       => 'After-Hours Login',
                    'description' => "{$name} logged in at " . Carbon::parse($log->created_at)->format('g:ia')
                        . ', outside normal business hours.',
                    'timestamp'   => Carbon::parse($log->created_at)->diffForHumans(),
                ];
            }
        }

        // 3. High deletion activity (5+ deletes by same user in 30 min)
        $deletionRows = UserActivityLog::where('event_type', 'component_action')
            ->where('created_at', '>=', $now->copy()->subMinutes(30))
            ->where(function ($q) {
                $q->where('action_name', 'like', 'delete%')
                  ->orWhere('action_name', 'like', 'bulkDelete%')
                  ->orWhere('action_name', 'like', 'remove%');
            })
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as c, MAX(created_at) as last_at')
            ->groupBy('user_id')
            ->having('c', '>=', 5)
            ->toBase()
            ->get();

        if ($deletionRows->isNotEmpty()) {
            $userNames = User::whereIn('id', $deletionRows->pluck('user_id'))->pluck('name', 'id');
            foreach ($deletionRows as $row) {
                $name = $userNames[$row->user_id] ?? 'A user';
                $alerts[] = (object) [
                    'severity'    => 'red',
                    'title'       => 'High Deletion Activity',
                    'description' => "{$name} deleted or removed {$row->c} records in the last 30 minutes.",
                    'timestamp'   => Carbon::parse($row->last_at)->diffForHumans(),
                ];
            }
        }

        // 4. Unusual accounting access — accessed today but no history in past 30 days
        $todayAccountingUsers = UserActivityLog::today()
            ->where('module', 'accounting')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        if ($todayAccountingUsers->isNotEmpty()) {
            $recentUsers = UserActivityLog::where('module', 'accounting')
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->where('created_at', '<', Carbon::today())
                ->whereIn('user_id', $todayAccountingUsers)
                ->distinct()
                ->pluck('user_id');

            $unusualIds = $todayAccountingUsers->diff($recentUsers);

            if ($unusualIds->isNotEmpty()) {
                $users = User::whereIn('id', $unusualIds)->get(['id', 'name']);
                foreach ($users as $user) {
                    $alerts[] = (object) [
                        'severity'    => 'amber',
                        'title'       => 'Unusual Section Access',
                        'description' => "{$user->name} accessed the Accounting section today"
                            . ' but has no prior history in that section.',
                        'timestamp'   => 'Today',
                    ];
                }
            }
        }

        return $alerts;
    }
}
