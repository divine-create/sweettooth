<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Item;
use App\Models\Stock;
use App\Models\UnitOfMeasure;

class ImportInventoryData extends Command
{
    protected $signature = 'import:inventory-data {csv_path}';
    protected $description = 'Delete all inventory data and import from CSV';

    public function handle()
    {
        $csvPath = $this->argument('csv_path');
        
        if (!file_exists($csvPath)) {
            $this->error("CSV file does not exist: {$csvPath}");
            return 1;
        }

        $this->info('Starting inventory data import...');
        
        // Truncate existing data
        $this->info('Deleting existing inventory data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Item::truncate();
        Stock::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('Reading CSV data...');
        $csvData = $this->readCsv($csvPath);
        
        if (empty($csvData)) {
            $this->error('No data found in CSV file.');
            return 1;
        }

        $this->info('Processing ' . count($csvData) . ' records...');
        
        $branchId = $this->getFirstBranchId();
        if (!$branchId) {
            $this->error('No branches found. Please create a branch first.');
            return 1;
        }

        $processedCount = 0;
        $failedRecords = [];

        foreach ($csvData as $index => $row) {
            try {
                // Skip header row
                if ($index === 0) continue;

                $product = trim($row[0]);
                $location = trim($row[1]);
                $measurement = trim($row[2]);
                $currentStock = trim($row[3]);

                if (empty($product)) {
                    continue;
                }

                // Create item
                $uomId = $this->resolveUomId($measurement);

                $item = Item::create([
                    'branch_id' => $branchId,
                    'name' => $product,
                    'sku' => $this->generateSku($product),
                    'category' => $this->classifyCategory($product),
                    'uom_id' => $uomId,
                    'status' => 'active',
                ]);

                // Create corresponding stock record
                Stock::create([
                    'branch_id' => $branchId,
                    'item_id' => $item->id,
                    'quantity_available' => $this->parseQuantity($currentStock),
                    'quantity_reserved' => 0,
                    'quantity_damaged' => 0,
                    'average_cost' => 0,
                    'health_status' => 'good',
                ]);

                $processedCount++;
                
                if ($processedCount % 100 === 0) {
                    $this->info("Processed {$processedCount} records...");
                }
                
            } catch (\Exception $e) {
                $failedRecords[] = [
                    'index' => $index,
                    'product' => $row[0] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                $this->warn("Failed to process record at index {$index}: " . $e->getMessage());
            }
        }

        if (!empty($failedRecords)) {
            $this->warn(count($failedRecords) . ' records failed to process.');
        }

        $this->info("Import completed successfully!");
        $this->info("Processed: {$processedCount} records");
        $this->info("Failed: " . count($failedRecords) . " records");

        return 0;
    }

    private function readCsv($filePath)
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception("Could not open CSV file: {$filePath}");
        }
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            $data[] = $row;
        }
        
        fclose($handle);
        return $data;
    }

    private function getFirstBranchId()
    {
        $branch = DB::table('branches')->first();
        return $branch ? $branch->id : null;
    }

    private function generateSku($productName)
    {
        // Generate a unique SKU based on product name
        $baseSku = Str::slug($productName, '_');
        
        // Ensure uniqueness by appending a number if needed
        $counter = 1;
        $sku = $baseSku;
        
        while (Item::where('sku', $sku)->exists()) {
            $sku = $baseSku . '_' . $counter;
            $counter++;
        }
        
        return $sku;
    }

    private function classifyCategory($productName)
    {
        $productName = strtolower($productName);
        
        // Equipment category
        if (preg_match('/(machine|oven|freezer|refrigerator|mixer|blender|grinder|cooker|pot|pan|kettle|scale|thermometer|timer|dishwasher|dish|plate|cup|glass|spoon|fork|knife|cutlery|utensil|tool|brush|sponge|cleaner|sanitizer|soap)/i', $productName)) {
            return 'equipment';
        }
        
        // Packaging category
        if (preg_match('/(box|bag|pack|wrap|container|tray|foil|film|paper|cup|plate|sleeve|nylon|sack|basket|bottle|jar|cylinder|holder|case|cover|lid|seal|tape|label)/i', $productName)) {
            return 'packaging';
        }
        
        // Raw material category
        if (preg_match('/(flour|sugar|salt|pepper|oil|butter|milk|egg|cheese|cream|yogurt|fruit|vegetable|meat|fish|seafood|grain|rhubarb|spice|herb|seasoning|powder|extract|syrup|sauce|jam|honey|chocolate|cocoa|coffee|tea|water|juice|wine|beer|liquor|rum|gin|vodka|whiskey|brandy|cognac|liqueur|wine|champagne|sparkling|soda|soft drink|cola|fanta|sprite|tonic|ginger ale|lemonade|iced tea|energy drink|sports drink|smoothie|shake|milkshake|yogurt|ice cream|frozen|dairy|condensed milk|powdered milk|evaporated milk|coconut milk|almond milk|soy milk|oat milk|rice milk|nut milk|plant milk|almond|walnut|peanut|cashew|pistachio|pecan|macadamia|brazil nut|chestnut|coconut|hazelnut|pea|bean|lentil|chickpea|quinoa|barley|oats|wheat|rice|corn|maize|potato|sweet potato|yam|taro|onion|garlic|ginger|carrot|celery|cucumber|tomato|potato|lettuce|spinach|kale|cabbage|broccoli|cauliflower|pepper|chili|jalapeno|bell pepper|cucumber|zucchini|squash|eggplant|mushroom|avocado|apple|banana|orange|lemon|lime|grape|strawberry|blueberry|raspberry|blackberry|cherry|plum|peach|apricot|pear|mango|pineapple|papaya|kiwi|fig|date|raisin|prune|olive|coconut|nutmeg|cinnamon|cardamom|cloves|vanilla|cumin|coriander|paprika|turmeric|cayenne|oregano|basil|thyme|rosemary|parsley|cilantro|mint|sage|marjoram|tarragon|dill|fennel|anise|star anise|allspice|mustard|horseradish|wasabi|soy sauce|fish sauce|oyster sauce|hoisin sauce|sriracha|tabasco|worcestershire|bbq sauce|ketchup|mayonnaise|mustard|vinegar|wine vinegar|balsamic vinegar|apple cider vinegar|rice vinegar|sherry vinegar|port wine vinegar|cooking wine|sherry|port|madeira|marsala|cooking sherry|cooking sake|mirin|sake|rice wine|cooking oil|olive oil|vegetable oil|canola oil|sunflower oil|peanut oil|sesame oil|coconut oil|lard|shortening|baking powder|baking soda|yeast|gelatin|agar|pectin|carrageenan|guar gum|xanthan gum|lecithin|emulsifier|preservative|antioxidant|flavor|flavour|aroma|essence|concentrate|extract|powder|crystal|granule|cube|tablet|capsule|lozenge|syrup|paste|puree|mash|juice concentrate|nectar|coulis|compote|jam|jelly|marmalade|preserves|chutney|pickle|relish|salsa|guacamole|hummus|tzatziki|aioli|remoulade|ranch|blue cheese|italian|french|caesar|thousand island|balsamic|vinaigrette|pesto|tapenade|harissa|sambal|curry paste|tomato paste|tomato sauce|pizza sauce|marinara|alfredo|carbonara|bechamel|veloute|espagnole|hollandaise|mayo|mustard|ketchup|bbq|hot sauce|sriracha|tabasco|worcestershire|soy|fish|oyster|hoisin|teriyaki|ponzu|dipping sauce|dip|spread|butter|margarine|cream cheese|philadelphia|neufchatel|ricotta|mascarpone|cream|milk|yogurt|kefir|buttermilk|sour cream|clotted cream|whipped cream|heavy cream|light cream|half and half|evaporated|condensed|powdered|dry milk|whey|casein|protein|whey protein|casein protein|soy protein|pea protein|rice protein|potato protein|oat protein|flax|chia|hemp|quinoa|amaranth|teff|millet|sorghum|buckwheat|oat|barley|wheat|rye|spelt|kamut|farro|bulgur|couscous|quinoa|rice|wild rice|brown rice|white rice|jasmine rice|basmati rice|arborio rice|sushi rice|sticky rice|instant rice|quick cooking rice|steel cut oats|rolled oats|instant oats|steel cut|rolled|quick|instant|flaked|milled|ground|powdered|crushed|chopped|minced|diced|sliced|shredded|grated|flaked|whole|raw|fresh|dried|dehydrated|freeze dried|canned|jarred|boxed|bagged|packed|processed|prepared|ready|instant|quick|fast|easy|simple|basic|standard|regular|normal|usual|common|typical|ordinary|everyday|household|kitchen|pantry|store|grocery|market|supermarket|wholesale|retail|bulk|commercial|industrial|restaurant|cafe|bakery|pastry|confectionery|candy|sweets|dessert|cake|cookie|biscuit|pastry|bread|roll|muffin|scone|croissant|bagel|bun|roll|flatbread|naan|pita|tortilla|chapati|roti|paratha|dosa|crepe|pancake|waffle|fritter|donut|doughnut|baguette|ciabatta|focaccia|sourdough|rye|multigrain|whole wheat|gluten free|organic|natural|raw|unprocessed|processed|refined|unrefined|fortified|enriched|vitamin|mineral|calcium|iron|zinc|magnesium|potassium|sodium|chloride|phosphorus|sulfur|manganese|fluoride|iodine|molybdenum|chromium|selenium|copper|cobalt|manganese|boron|nickel|silicon|vanadium|tin|arsenic|bromine|rubidium|strontium|yttrium|zirconium|niobium|molybdenum|technetium|ruthenium|rhodium|palladium|silver|cadmium|indium|tin|antimony|tellurium|iodine|xenon|cesium|barium|lanthanum|cerium|praseodymium|neodymium|promethium|samarium|europium|gadolinium|terbium|dysprosium|holmium|erbium|thulium|ytterbium|lutetium|actinium|thorium|protactinium|uranium|neptunium|plutonium|americium|curium|berkelium|californium|einsteinium|fermium|mendelevium|nobelium|lawrencium|radium|actinium|thorium|protactinium|uranium|neptunium|plutonium|americium|curium|berkelium|californium|einsteinium|fermium|mendelevium|nobelium|lawrencium)/i', $productName)) {
            return 'raw_material';
        }
        
        // Default to consumable
        return 'consumable';
    }

    private function convertUom($measurement)
    {
        $measurement = strtoupper(trim($measurement));
        
        switch ($measurement) {
            case 'PCS':
            case 'PIECES':
            case 'PC':
                return 'pcs';
            case 'GRAM':
            case 'GRAMS':
            case 'GM':
            case 'G':
                return 'grams';
            case 'KG':
            case 'KILOGRAM':
            case 'KILOGRAMS':
                return 'kg';
            case 'LITER':
            case 'LITERS':
            case 'LTR':
            case 'L':
                return 'liters';
            case 'ML':
            case 'MILLILITER':
            case 'MILLILITERS':
                return 'ml';
            case 'UNITS':
                return 'units';
            case 'BAG':
            case 'BAGS':
                return 'bags';
            case 'CARTON':
            case 'CARTONS':
                return 'cartons';
            case 'PK':
            case 'PKS':
                return 'units'; // Using units for packs
            case 'BTL':
            case 'BTLS':
                return 'units'; // Using units for bottles
            case 'BAGS':
                return 'bags';
            default:
                return 'units'; // Default fallback
        }
    }

    private function resolveUomId($measurement): ?int
    {
        $codeMap = [
            'grams' => 'g',
            'kg' => 'kg',
            'liters' => 'l',
            'ml' => 'ml',
            'pcs' => 'pcs',
            'units' => 'unit',
            'bags' => 'unit',
            'cartons' => 'unit',
        ];

        $legacy = $this->convertUom($measurement);
        $code = $codeMap[$legacy] ?? null;

        if (! $code) {
            return null;
        }

        return UnitOfMeasure::query()->where('code', $code)->value('id');
    }

    private function parseQuantity($quantity)
    {
        if (empty($quantity) || $quantity === '') {
            return 0;
        }
        
        // Remove any non-numeric characters except decimal points
        $cleaned = preg_replace('/[^0-9.]/', '', $quantity);
        
        if ($cleaned === '' || !is_numeric($cleaned)) {
            return 0;
        }
        
        return floatval($cleaned);
    }
}
