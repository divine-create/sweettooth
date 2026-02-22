<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class WorkflowValidationException extends Exception
{
    protected string $currentState;
    protected string $requiredState;
    protected string $redirectRoute;

    public function __construct(
        string $message,
        string $currentState,
        string $requiredState,
        string $redirectRoute
    ) {
        parent::__construct($message);
        $this->currentState = $currentState;
        $this->requiredState = $requiredState;
        $this->redirectRoute = $redirectRoute;
    }

    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    public function getRequiredState(): string
    {
        return $this->requiredState;
    }

    public function getRedirectRoute(): string
    {
        return $this->redirectRoute;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Workflow validation failed',
                'message' => $this->getMessage(),
                'current_state' => $this->currentState,
                'required_state' => $this->requiredState,
                'redirect_route' => $this->redirectRoute
            ], 403);
        }

        return redirect($this->redirectRoute)->with('error', $this->getMessage());
    }

    /**
     * Create an exception for stock verification required
     */
    public static function stockVerificationRequired(string $redirectRoute): self
    {
        return new self(
            'Stock verification is required before accessing POS.',
            'clock_in',
            'stock_opening',
            $redirectRoute
        );
    }

    /**
     * Create an exception for shift closing required
     */
    public static function shiftClosingRequired(string $redirectRoute): self
    {
        return new self(
            'Please complete shift closing to finalize your shift.',
            'clock_out',
            'shift_closing',
            $redirectRoute
        );
    }

    /**
     * Create an exception for clock-in required
     */
    public static function clockInRequired(string $redirectRoute): self
    {
        return new self(
            'Please clock in to start your shift.',
            'none',
            'clock_in',
            $redirectRoute
        );
    }
}
