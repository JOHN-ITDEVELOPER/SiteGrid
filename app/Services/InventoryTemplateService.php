<?php

namespace App\Services;

use App\Models\Site;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryTemplateService
{
    /**
     * Get all available industry templates
     */
    public static function getAvailableTemplates(): array
    {
        return [
            'construction' => [
                'label' => 'Construction & Building',
                'description' => 'For construction sites, renovation, and building projects',
                'categories' => [
                    [
                        'name' => 'Cement & Binders',
                        'type' => 'material',
                        'description' => 'Concrete mixing materials',
                        'items' => [
                            ['name' => 'OPC Cement 50kg', 'sku' => 'CEMENT-OPC-50', 'unit' => 'bag'],
                            ['name' => 'PPC Cement 50kg', 'sku' => 'CEMENT-PPC-50', 'unit' => 'bag'],
                            ['name' => 'Sand', 'sku' => 'SAND-BULK', 'unit' => 'ton'],
                        ],
                    ],
                    [
                        'name' => 'Steel & Metals',
                        'type' => 'material',
                        'description' => 'Reinforcement and structural steel',
                        'items' => [
                            ['name' => 'Rebar 12mm', 'sku' => 'REBAR-12', 'unit' => 'piece'],
                            ['name' => 'Rebar 16mm', 'sku' => 'REBAR-16', 'unit' => 'piece'],
                            ['name' => 'Wire Mesh', 'sku' => 'MESH-WIRE', 'unit' => 'sheet'],
                        ],
                    ],
                    [
                        'name' => 'Hand Tools',
                        'type' => 'tool',
                        'description' => 'Manual tools for on-site work',
                        'items' => [
                            ['name' => 'Shovel', 'sku' => 'TOOL-SHOVEL', 'unit' => 'piece'],
                            ['name' => 'Pickaxe', 'sku' => 'TOOL-PICKAXE', 'unit' => 'piece'],
                            ['name' => 'Wheelbarrow', 'sku' => 'TOOL-WHEELBARROW', 'unit' => 'piece'],
                            ['name' => 'Hammer', 'sku' => 'TOOL-HAMMER', 'unit' => 'piece'],
                        ],
                    ],
                    [
                        'name' => 'Heavy Equipment',
                        'type' => 'equipment',
                        'description' => 'Large machinery and power equipment',
                        'items' => [
                            ['name' => 'Concrete Mixer', 'sku' => 'EQUIP-MIXER', 'unit' => 'piece'],
                            ['name' => 'Generator 5kVA', 'sku' => 'EQUIP-GEN-5', 'unit' => 'piece'],
                            ['name' => 'Vibrator', 'sku' => 'EQUIP-VIBRATOR', 'unit' => 'piece'],
                        ],
                    ],
                ],
            ],
            'hospitality' => [
                'label' => 'Hospitality & Hotels',
                'description' => 'For hotels, restaurants, and hospitality businesses',
                'categories' => [
                    [
                        'name' => 'Kitchen Supplies',
                        'type' => 'material',
                        'description' => 'Food service and preparation supplies',
                        'items' => [
                            ['name' => 'Cooking Oil (5L)', 'sku' => 'FOOD-OIL-5L', 'unit' => 'bottle'],
                            ['name' => 'Sugar (25kg)', 'sku' => 'FOOD-SUGAR-25', 'unit' => 'bag'],
                            ['name' => 'Salt (10kg)', 'sku' => 'FOOD-SALT-10', 'unit' => 'bag'],
                        ],
                    ],
                    [
                        'name' => 'Linens & Textiles',
                        'type' => 'material',
                        'description' => 'Bedding, towels, and fabric items',
                        'items' => [
                            ['name' => 'Double Bed Sheets', 'sku' => 'LINEN-BED-DOUBLE', 'unit' => 'piece'],
                            ['name' => 'Bath Towels', 'sku' => 'LINEN-TOWEL-BATH', 'unit' => 'piece'],
                            ['name' => 'Hand Towels', 'sku' => 'LINEN-TOWEL-HAND', 'unit' => 'piece'],
                        ],
                    ],
                    [
                        'name' => 'Cleaning & Hygiene',
                        'type' => 'material',
                        'description' => 'Cleaning and sanitation products',
                        'items' => [
                            ['name' => 'Disinfectant (5L)', 'sku' => 'CLEAN-DISINFECT-5L', 'unit' => 'bottle'],
                            ['name' => 'Hand Soap (1L)', 'sku' => 'CLEAN-SOAP-1L', 'unit' => 'bottle'],
                            ['name' => 'Toilet Paper (Rolls)', 'sku' => 'CLEAN-TP-ROLLS', 'unit' => 'pack'],
                        ],
                    ],
                    [
                        'name' => 'Kitchen Equipment',
                        'type' => 'equipment',
                        'description' => 'Major kitchen appliances and tools',
                        'items' => [
                            ['name' => 'Commercial Stove', 'sku' => 'EQUIP-STOVE', 'unit' => 'piece'],
                            ['name' => 'Refrigerator', 'sku' => 'EQUIP-FRIDGE', 'unit' => 'piece'],
                            ['name' => 'Food Warmer', 'sku' => 'EQUIP-WARMER', 'unit' => 'piece'],
                        ],
                    ],
                ],
            ],
            'retail' => [
                'label' => 'Retail & Shops',
                'description' => 'For retail stores and shopping businesses',
                'categories' => [
                    [
                        'name' => 'Merchandise',
                        'type' => 'material',
                        'description' => 'Products for resale',
                        'items' => [
                            ['name' => 'General Stock Items', 'sku' => 'RETAIL-STOCK-001', 'unit' => 'piece'],
                        ],
                    ],
                    [
                        'name' => 'Packaging & Supplies',
                        'type' => 'material',
                        'description' => 'Bags, boxes, and packaging materials',
                        'items' => [
                            ['name' => 'Plastic Bags (Small)', 'sku' => 'PACK-BAG-SM', 'unit' => 'pack'],
                            ['name' => 'Plastic Bags (Large)', 'sku' => 'PACK-BAG-LG', 'unit' => 'pack'],
                            ['name' => 'Cardboard Boxes', 'sku' => 'PACK-BOX-CARD', 'unit' => 'piece'],
                        ],
                    ],
                    [
                        'name' => 'Display Equipment',
                        'type' => 'equipment',
                        'description' => 'Shelving and display units',
                        'items' => [
                            ['name' => 'Display Shelves', 'sku' => 'DISP-SHELF', 'unit' => 'piece'],
                            ['name' => 'Product Stand', 'sku' => 'DISP-STAND', 'unit' => 'piece'],
                        ],
                    ],
                    [
                        'name' => 'POS & Tools',
                        'type' => 'tool',
                        'description' => 'Point of sale and operational tools',
                        'items' => [
                            ['name' => 'Receipt Paper', 'sku' => 'POS-RECEIPT-PAPER', 'unit' => 'roll'],
                            ['name' => 'Price Labels', 'sku' => 'POS-LABEL', 'unit' => 'pack'],
                        ],
                    ],
                ],
            ],
            'healthcare' => [
                'label' => 'Healthcare & Medical',
                'description' => 'For clinics, hospitals, and healthcare facilities',
                'categories' => [
                    [
                        'name' => 'Medical Supplies',
                        'type' => 'material',
                        'description' => 'Consumable medical items',
                        'items' => [
                            ['name' => 'Sterile Gloves (Box)', 'sku' => 'MED-GLOVE-BOX', 'unit' => 'box'],
                            ['name' => 'Masks (Box of 50)', 'sku' => 'MED-MASK-50', 'unit' => 'box'],
                            ['name' => 'Antiseptic Solution (500ml)', 'sku' => 'MED-ANTISEPTIC-500', 'unit' => 'bottle'],
                            ['name' => 'Bandages Assorted', 'sku' => 'MED-BANDAGE-ASSRT', 'unit' => 'box'],
                        ],
                    ],
                    [
                        'name' => 'PPE (Personal Protective Equipment)',
                        'type' => 'material',
                        'description' => 'Safety and protective gear',
                        'items' => [
                            ['name' => 'Protective Gowns', 'sku' => 'PPE-GOWN', 'unit' => 'piece'],
                            ['name' => 'Face Shields', 'sku' => 'PPE-SHIELD', 'unit' => 'piece'],
                            ['name' => 'Shoe Covers', 'sku' => 'PPE-SHOES', 'unit' => 'pack'],
                        ],
                    ],
                    [
                        'name' => 'Medical Equipment',
                        'type' => 'equipment',
                        'description' => 'Diagnostic and therapeutic equipment',
                        'items' => [
                            ['name' => 'Blood Pressure Monitor', 'sku' => 'EQUIP-BP-MONITOR', 'unit' => 'piece'],
                            ['name' => 'Thermometer Digital', 'sku' => 'EQUIP-THERMO', 'unit' => 'piece'],
                            ['name' => 'Oxygen Concentrator', 'sku' => 'EQUIP-O2', 'unit' => 'piece'],
                        ],
                    ],
                ],
            ],
            'manufacturing' => [
                'label' => 'Manufacturing',
                'description' => 'For manufacturing plants and factories',
                'categories' => [
                    [
                        'name' => 'Raw Materials',
                        'type' => 'material',
                        'description' => 'Input materials for production',
                        'items' => [
                            ['name' => 'Raw Metal Stock', 'sku' => 'MANU-METAL-RAW', 'unit' => 'kg'],
                            ['name' => 'Plastic Pellets', 'sku' => 'MANU-PLASTIC-PELLET', 'unit' => 'kg'],
                            ['name' => 'Adhesive (10L)', 'sku' => 'MANU-ADHESIVE-10L', 'unit' => 'drum'],
                        ],
                    ],
                    [
                        'name' => 'Components & Parts',
                        'type' => 'material',
                        'description' => 'Sub-assemblies and components',
                        'items' => [
                            ['name' => 'Bolts & Nuts (1kg)', 'sku' => 'PART-BOLT-1KG', 'unit' => 'pack'],
                            ['name' => 'Fasteners', 'sku' => 'PART-FASTENER', 'unit' => 'box'],
                        ],
                    ],
                    [
                        'name' => 'Manufacturing Tools',
                        'type' => 'tool',
                        'description' => 'Hand and precision tools',
                        'items' => [
                            ['name' => 'Wrench Set', 'sku' => 'TOOL-WRENCH', 'unit' => 'set'],
                            ['name' => 'Measuring Calipers', 'sku' => 'TOOL-CALIPERS', 'unit' => 'piece'],
                        ],
                    ],
                    [
                        'name' => 'Production Equipment',
                        'type' => 'equipment',
                        'description' => 'Industrial machinery',
                        'items' => [
                            ['name' => 'Assembly Line Motor', 'sku' => 'EQUIP-MOTOR', 'unit' => 'piece'],
                            ['name' => 'Welding Machine', 'sku' => 'EQUIP-WELDER', 'unit' => 'piece'],
                            ['name' => 'Hydraulic Press', 'sku' => 'EQUIP-PRESS', 'unit' => 'piece'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Apply a template to a site
     */
    public static function applyTemplate(Site $site, string $templateKey): void
    {
        $templates = self::getAvailableTemplates();
        
        if (!isset($templates[$templateKey])) {
            throw new \InvalidArgumentException("Template '{$templateKey}' not found");
        }

        $template = $templates[$templateKey];

        DB::transaction(function () use ($site, $template) {
            foreach ($template['categories'] as $categoryData) {
                $category = InventoryCategory::firstOrCreate(
                    [
                        'site_id' => $site->id,
                        'name' => $categoryData['name'],
                        'type' => $categoryData['type'],
                    ],
                    [
                        'description' => $categoryData['description'] ?? null,
                    ]
                );

                foreach ($categoryData['items'] as $itemData) {
                    InventoryItem::firstOrCreate(
                        [
                            'site_id' => $site->id,
                            'category_id' => $category->id,
                            'name' => $itemData['name'],
                        ],
                        [
                            'sku' => $itemData['sku'] ?? null,
                            'unit' => $itemData['unit'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        });
    }
}
