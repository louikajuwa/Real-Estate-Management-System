<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Inquiry;
use App\Models\Booking;
use App\Models\ClientInteraction;
use App\Enums\RoleName;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\InquiryStatus;
use App\Enums\BookingStatus;
use App\Enums\InteractionType;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve the demo agency (created by AgencySeeder)
        $agency = \App\Models\Agency::where('slug', 'sunrise-realty')->first();
        $agencyId = $agency?->id;

        // -------------------------------------------------------------------------
        // 1. Admin User (scoped to demo agency)
        // -------------------------------------------------------------------------
        try {
            $adminUser = User::firstOrCreate(
                ['email' => env('ADMIN_EMAIL', 'admin@realestate.com')],
                [
                    'name'      => env('ADMIN_NAME', 'Admin User'),
                    'password'  => Hash::make(env('ADMIN_PASSWORD', 'password')),
                    'agency_id' => $agencyId,
                    'is_active' => true,
                ]
            );
            if ($adminUser->agency_id !== $agencyId) {
                $adminUser->update(['agency_id' => $agencyId]);
            }
            $adminUser->syncRoles([RoleName::Admin->value]);
            $this->command->info('Admin user seeded.');
        } catch (\Throwable $e) {
            $this->command->error('Admin seeding failed: ' . $e->getMessage());
        }

        // -------------------------------------------------------------------------
        // 2. Agent Users
        // -------------------------------------------------------------------------
        $agentRecords = [];
        $agentData = [
            [
                'name'       => 'Ahmed Al Mansouri',
                'email'      => 'agent1@realestate.com',
                'license_no' => 'RE-AG-001',
                'bio'        => 'Experienced agent specialising in Dubai luxury properties with over 8 years in the market.',
                'phone'      => '+971501234567',
            ],
            [
                'name'       => 'Sara Johnson',
                'email'      => 'agent2@realestate.com',
                'license_no' => 'RE-AG-002',
                'bio'        => 'Abu Dhabi residential and commercial property expert with a strong client portfolio.',
                'phone'      => '+971502345678',
            ],
            [
                'name'       => 'Khalid Hassan',
                'email'      => 'agent3@realestate.com',
                'license_no' => 'RE-AG-003',
                'bio'        => 'Sharjah-based agent focused on affordable family homes and investment properties.',
                'phone'      => '+971503456789',
            ],
        ];

        try {
            foreach ($agentData as $data) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name'      => $data['name'],
                        'password'  => Hash::make('password'),
                        'phone'     => $data['phone'],
                        'agency_id' => $agencyId,
                        'is_active' => true,
                    ]
                );
                if ($user->agency_id !== $agencyId) {
                    $user->update(['agency_id' => $agencyId]);
                }
                $user->syncRoles([RoleName::Agent->value]);

                $agent = Agent::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'agency_id'  => $agencyId,
                        'license_no' => $data['license_no'],
                        'bio'        => $data['bio'],
                        'phone'      => $data['phone'],
                    ]
                );
                if ($agent->agency_id !== $agencyId) {
                    $agent->update(['agency_id' => $agencyId]);
                }

                $agentRecords[] = $agent;
            }
            $this->command->info('Agent users seeded.');
        } catch (\Throwable $e) {
            $this->command->error('Agent seeding failed: ' . $e->getMessage());
        }

        // -------------------------------------------------------------------------
        // 3. Client Users
        // -------------------------------------------------------------------------
        $clientRecords = [];
        $clientData = [
            // agent1 clients
            ['name' => 'Mohammed Al Zaabi',   'email' => 'client1@realestate.com', 'phone' => '+971504111111', 'agent_index' => 0],
            ['name' => 'Priya Nair',           'email' => 'client2@realestate.com', 'phone' => '+971504222222', 'agent_index' => 0],
            // agent2 clients
            ['name' => 'James Thompson',       'email' => 'client3@realestate.com', 'phone' => '+971504333333', 'agent_index' => 1],
            ['name' => 'Fatima Al Rashid',     'email' => 'client4@realestate.com', 'phone' => '+971504444444', 'agent_index' => 1],
            // agent3 clients
            ['name' => 'David Kim',            'email' => 'client5@realestate.com', 'phone' => '+971504555555', 'agent_index' => 2],
            ['name' => 'Aisha Bint Sultan',    'email' => 'client6@realestate.com', 'phone' => '+971504666666', 'agent_index' => 2],
        ];

        try {
            foreach ($clientData as $data) {
                $assignedAgent = $agentRecords[$data['agent_index']] ?? null;

                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name'      => $data['name'],
                        'password'  => Hash::make('password'),
                        'phone'     => $data['phone'],
                        'is_active' => true,
                    ]
                );
                $user->syncRoles([RoleName::Client->value]);

                $client = Client::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'agency_id'         => $assignedAgent?->agency_id,
                        'assigned_agent_id' => $assignedAgent?->id,
                        'name'              => $data['name'],
                        'email'             => $data['email'],
                        'phone'             => $data['phone'],
                    ]
                );

                $clientRecords[] = $client;
            }
            $this->command->info('Client users seeded.');
        } catch (\Throwable $e) {
            $this->command->error('Client seeding failed: ' . $e->getMessage());
        }

        // -------------------------------------------------------------------------
        // 4. Properties (15 total)
        // -------------------------------------------------------------------------
        $propertyRecords = [];
        $propertiesData = [
            // Agent 1 – 5 properties
            [
                'agent_index' => 0,
                'title'       => 'Sea View Apartment in JBR',
                'type'        => PropertyType::Apartment,
                'status'      => PropertyStatus::Available,
                'price'       => 1250000,
                'city'        => 'Dubai',
                'address'     => 'Jumeirah Beach Residence, Dubai Marina',
                'bedrooms'    => 2,
                'bathrooms'   => 2,
                'area_sqft'   => 1200,
                'is_featured' => true,
                'description' => 'Stunning sea view apartment in the heart of JBR with premium finishes and direct beach access.',
            ],
            [
                'agent_index' => 0,
                'title'       => 'Luxury Villa with Private Pool',
                'type'        => PropertyType::Villa,
                'status'      => PropertyStatus::Available,
                'price'       => 4800000,
                'city'        => 'Dubai',
                'address'     => 'Palm Jumeirah, Dubai',
                'bedrooms'    => 5,
                'bathrooms'   => 6,
                'area_sqft'   => 6500,
                'is_featured' => true,
                'description' => 'Magnificent palm-front villa with a private pool, home cinema, and staff quarters.',
            ],
            [
                'agent_index' => 0,
                'title'       => 'Downtown Office Space',
                'type'        => PropertyType::Office,
                'status'      => PropertyStatus::Available,
                'price'       => 2100000,
                'city'        => 'Dubai',
                'address'     => 'Sheikh Zayed Road, Downtown Dubai',
                'bedrooms'    => 0,
                'bathrooms'   => 2,
                'area_sqft'   => 2400,
                'is_featured' => false,
                'description' => 'Premium grade-A office space with panoramic city views and full fitout.',
            ],
            [
                'agent_index' => 0,
                'title'       => 'Cosy Studio in Business Bay',
                'type'        => PropertyType::Apartment,
                'status'      => PropertyStatus::Reserved,
                'price'       => 620000,
                'city'        => 'Dubai',
                'address'     => 'Business Bay, Dubai',
                'bedrooms'    => 0,
                'bathrooms'   => 1,
                'area_sqft'   => 520,
                'is_featured' => false,
                'description' => 'Well-maintained studio in Business Bay with canal views and building amenities.',
            ],
            [
                'agent_index' => 0,
                'title'       => 'Commercial Plot – Investment Opportunity',
                'type'        => PropertyType::Land,
                'status'      => PropertyStatus::Available,
                'price'       => 3500000,
                'city'        => 'Dubai',
                'address'     => 'Dubai Investment Park',
                'bedrooms'    => 0,
                'bathrooms'   => 0,
                'area_sqft'   => 15000,
                'is_featured' => false,
                'description' => 'Prime commercial land in Dubai Investment Park with full infrastructure and road access.',
            ],
            // Agent 2 – 5 properties
            [
                'agent_index' => 1,
                'title'       => 'Family Villa with Pool – Al Raha',
                'type'        => PropertyType::Villa,
                'status'      => PropertyStatus::Available,
                'price'       => 3200000,
                'city'        => 'Abu Dhabi',
                'address'     => 'Al Raha Gardens, Abu Dhabi',
                'bedrooms'    => 4,
                'bathrooms'   => 4,
                'area_sqft'   => 4800,
                'is_featured' => true,
                'description' => 'Spacious family villa in gated Al Raha Gardens with private pool, landscaped garden, and maid\'s room.',
            ],
            [
                'agent_index' => 1,
                'title'       => 'Modern 3-Bedroom Apartment',
                'type'        => PropertyType::Apartment,
                'status'      => PropertyStatus::Available,
                'price'       => 980000,
                'city'        => 'Abu Dhabi',
                'address'     => 'Reem Island, Abu Dhabi',
                'bedrooms'    => 3,
                'bathrooms'   => 3,
                'area_sqft'   => 1750,
                'is_featured' => false,
                'description' => 'Contemporary apartment on Reem Island with sea views, gym, and covered parking.',
            ],
            [
                'agent_index' => 1,
                'title'       => 'Retail Commercial Unit',
                'type'        => PropertyType::Commercial,
                'status'      => PropertyStatus::Sold,
                'price'       => 1450000,
                'city'        => 'Abu Dhabi',
                'address'     => 'Mussafah Industrial Area, Abu Dhabi',
                'bedrooms'    => 0,
                'bathrooms'   => 1,
                'area_sqft'   => 3200,
                'is_featured' => false,
                'description' => 'Ground-floor retail unit in a high-footfall commercial building, fully fitted.',
            ],
            [
                'agent_index' => 1,
                'title'       => 'Penthouse with Corniche View',
                'type'        => PropertyType::Apartment,
                'status'      => PropertyStatus::Available,
                'price'       => 4200000,
                'city'        => 'Abu Dhabi',
                'address'     => 'Corniche Road, Abu Dhabi',
                'bedrooms'    => 4,
                'bathrooms'   => 5,
                'area_sqft'   => 5200,
                'is_featured' => true,
                'description' => 'Duplex penthouse with 360-degree Corniche views, private terrace, and smart home system.',
            ],
            [
                'agent_index' => 1,
                'title'       => 'Affordable Townhouse – Al Shamkha',
                'type'        => PropertyType::House,
                'status'      => PropertyStatus::Reserved,
                'price'       => 1100000,
                'city'        => 'Abu Dhabi',
                'address'     => 'Al Shamkha, Abu Dhabi',
                'bedrooms'    => 3,
                'bathrooms'   => 3,
                'area_sqft'   => 2100,
                'is_featured' => false,
                'description' => 'Brand-new townhouse in a quiet residential community with easy highway access.',
            ],
            // Agent 3 – 5 properties
            [
                'agent_index' => 2,
                'title'       => 'Starter Apartment – Al Nahda',
                'type'        => PropertyType::Apartment,
                'status'      => PropertyStatus::Available,
                'price'       => 520000,
                'city'        => 'Sharjah',
                'address'     => 'Al Nahda, Sharjah',
                'bedrooms'    => 1,
                'bathrooms'   => 1,
                'area_sqft'   => 780,
                'is_featured' => false,
                'description' => 'Affordable 1-bedroom apartment in Al Nahda close to Dubai border and public transport.',
            ],
            [
                'agent_index' => 2,
                'title'       => 'Spacious 3BHK House – Muwailih',
                'type'        => PropertyType::House,
                'status'      => PropertyStatus::Available,
                'price'       => 750000,
                'city'        => 'Sharjah',
                'address'     => 'Muwailih Commercial, Sharjah',
                'bedrooms'    => 3,
                'bathrooms'   => 2,
                'area_sqft'   => 2600,
                'is_featured' => false,
                'description' => 'Generously sized independent house with a private garden, ideal for families.',
            ],
            [
                'agent_index' => 2,
                'title'       => 'Industrial Warehouse Space',
                'type'        => PropertyType::Commercial,
                'status'      => PropertyStatus::Available,
                'price'       => 900000,
                'city'        => 'Sharjah',
                'address'     => 'SAIF Zone, Sharjah',
                'bedrooms'    => 0,
                'bathrooms'   => 2,
                'area_sqft'   => 8000,
                'is_featured' => false,
                'description' => 'Large warehouse unit in Sharjah Airport International Free Zone with loading bay.',
            ],
            [
                'agent_index' => 2,
                'title'       => 'Heritage District Villa',
                'type'        => PropertyType::Villa,
                'status'      => PropertyStatus::Sold,
                'price'       => 2300000,
                'city'        => 'Sharjah',
                'address'     => 'Al Qasba, Sharjah',
                'bedrooms'    => 4,
                'bathrooms'   => 4,
                'area_sqft'   => 3800,
                'is_featured' => true,
                'description' => 'Elegantly designed villa near Al Qasba canal with traditional architecture and modern interiors.',
            ],
            [
                'agent_index' => 2,
                'title'       => 'Freehold Land Plot – Tilal City',
                'type'        => PropertyType::Land,
                'status'      => PropertyStatus::Available,
                'price'       => 680000,
                'city'        => 'Sharjah',
                'address'     => 'Tilal City, Sharjah',
                'bedrooms'    => 0,
                'bathrooms'   => 0,
                'area_sqft'   => 5000,
                'is_featured' => false,
                'description' => 'Freehold residential plot in Tilal City master community, ready to build.',
            ],
        ];

        try {
            foreach ($propertiesData as $index => $data) {
                $agent = $agentRecords[$data['agent_index']] ?? null;
                $slug  = Str::slug($data['title']) . '-' . ($index + 1);

                $property = Property::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'agency_id'   => $agencyId,
                        'agent_id'    => $agent?->id,
                        'title'       => $data['title'],
                        'description' => $data['description'],
                        'type'        => $data['type'],
                        'price'       => $data['price'],
                        'address'     => $data['address'],
                        'city'        => $data['city'],
                        'bedrooms'    => $data['bedrooms'],
                        'bathrooms'   => $data['bathrooms'],
                        'area_sqft'   => $data['area_sqft'],
                        'status'      => $data['status'],
                        'is_featured' => $data['is_featured'],
                    ]
                );

                $propertyRecords[] = $property;

                // 2-3 images per property — picsum.photos for stable demo images
                $imageCount = ($index % 3 === 0) ? 3 : 2;
                for ($imgIdx = 1; $imgIdx <= $imageCount; $imgIdx++) {
                    $propNum = $index + 1;
                    $seed    = "re-p{$propNum}-i{$imgIdx}";
                    PropertyImage::firstOrCreate(
                        [
                            'property_id' => $property->id,
                            'path'        => "https://picsum.photos/seed/{$seed}/800/600",
                        ],
                        [
                            'is_primary'  => ($imgIdx === 1),
                            'sort_order'  => $imgIdx,
                        ]
                    );
                }
            }
            $this->command->info('Properties and images seeded.');
        } catch (\Throwable $e) {
            $this->command->error('Property seeding failed: ' . $e->getMessage());
        }

        // -------------------------------------------------------------------------
        // 5. Inquiries (10+, all 5 statuses covered)
        // -------------------------------------------------------------------------
        $inquiryRecords = [];
        $inquiriesData = [
            // status: new
            [
                'property_index' => 0,
                'client_index'   => 0,
                'agent_index'    => 0,
                'status'         => InquiryStatus::New,
                'message'        => 'I am interested in this sea view apartment. Could we arrange a viewing?',
            ],
            [
                'property_index' => 5,
                'client_index'   => 2,
                'agent_index'    => 1,
                'status'         => InquiryStatus::New,
                'message'        => 'Please send me more details on the Al Raha villa including floor plans.',
            ],
            // status: contacted
            [
                'property_index' => 1,
                'client_index'   => 1,
                'agent_index'    => 0,
                'status'         => InquiryStatus::Contacted,
                'message'        => 'We are very interested in the Palm Jumeirah villa. Is there flexibility on the price?',
            ],
            [
                'property_index' => 6,
                'client_index'   => 3,
                'agent_index'    => 1,
                'status'         => InquiryStatus::Contacted,
                'message'        => 'We would like to know about service charges for this Reem Island apartment.',
            ],
            // status: viewing_scheduled
            [
                'property_index' => 10,
                'client_index'   => 4,
                'agent_index'    => 2,
                'status'         => InquiryStatus::ViewingScheduled,
                'message'        => 'We have scheduled a viewing for the Al Nahda apartment next week.',
            ],
            [
                'property_index' => 11,
                'client_index'   => 5,
                'agent_index'    => 2,
                'status'         => InquiryStatus::ViewingScheduled,
                'message'        => 'Viewing confirmed for the Muwailih house on Saturday morning.',
            ],
            // status: negotiation
            [
                'property_index' => 8,
                'client_index'   => 2,
                'agent_index'    => 1,
                'status'         => InquiryStatus::Negotiation,
                'message'        => 'We are negotiating the price for the Corniche penthouse. Please send updated offer.',
            ],
            [
                'property_index' => 3,
                'client_index'   => 0,
                'agent_index'    => 0,
                'status'         => InquiryStatus::Negotiation,
                'message'        => 'We are in the final stages of negotiation for the Business Bay studio.',
            ],
            // status: closed
            [
                'property_index' => 7,
                'client_index'   => 3,
                'agent_index'    => 1,
                'status'         => InquiryStatus::Closed,
                'message'        => 'Transaction completed for the retail commercial unit in Mussafah.',
            ],
            [
                'property_index' => 13,
                'client_index'   => 4,
                'agent_index'    => 2,
                'status'         => InquiryStatus::Closed,
                'message'        => 'Sale concluded for the Heritage District Villa in Al Qasba.',
            ],
        ];

        try {
            foreach ($inquiriesData as $data) {
                $property = $propertyRecords[$data['property_index']] ?? null;
                $client   = $clientRecords[$data['client_index']] ?? null;
                $agent    = $agentRecords[$data['agent_index']] ?? null;

                if (!$property || !$client || !$agent) {
                    continue;
                }

                $inquiry = Inquiry::firstOrCreate(
                    [
                        'property_id' => $property->id,
                        'client_id'   => $client->id,
                    ],
                    [
                        'agency_id' => $agencyId,
                        'agent_id'  => $agent->id,
                        'message'   => $data['message'],
                        'status'    => $data['status'],
                    ]
                );

                $inquiryRecords[] = $inquiry;
            }
            $this->command->info('Inquiries seeded.');
        } catch (\Throwable $e) {
            $this->command->error('Inquiry seeding failed: ' . $e->getMessage());
        }

        // -------------------------------------------------------------------------
        // 6. Bookings (5+, mix of statuses and dates)
        // -------------------------------------------------------------------------
        $bookingsData = [
            [
                'property_index' => 0,
                'client_index'   => 0,
                'agent_index'    => 0,
                'status'         => BookingStatus::Scheduled,
                'scheduled_at'   => Carbon::now()->addDays(3)->setHour(10)->setMinute(0),
                'duration_min'   => 60,
                'notes'          => 'Client prefers morning viewing. Please have keys ready.',
            ],
            [
                'property_index' => 5,
                'client_index'   => 2,
                'agent_index'    => 1,
                'status'         => BookingStatus::Scheduled,
                'scheduled_at'   => Carbon::now()->addDays(5)->setHour(14)->setMinute(30),
                'duration_min'   => 90,
                'notes'          => 'Couple viewing with interior designer. Allow extra time.',
            ],
            [
                'property_index' => 10,
                'client_index'   => 4,
                'agent_index'    => 2,
                'status'         => BookingStatus::Scheduled,
                'scheduled_at'   => Carbon::now()->addDays(7)->setHour(11)->setMinute(0),
                'duration_min'   => 45,
                'notes'          => 'First-time buyer, please provide detailed walkthrough.',
            ],
            [
                'property_index' => 6,
                'client_index'   => 3,
                'agent_index'    => 1,
                'status'         => BookingStatus::Completed,
                'scheduled_at'   => Carbon::now()->subDays(10)->setHour(9)->setMinute(0),
                'duration_min'   => 60,
                'notes'          => 'Viewing completed. Client requested second viewing.',
            ],
            [
                'property_index' => 1,
                'client_index'   => 1,
                'agent_index'    => 0,
                'status'         => BookingStatus::Completed,
                'scheduled_at'   => Carbon::now()->subDays(5)->setHour(15)->setMinute(0),
                'duration_min'   => 120,
                'notes'          => 'Extended viewing of Palm villa completed. Offer submitted.',
            ],
            [
                'property_index' => 11,
                'client_index'   => 5,
                'agent_index'    => 2,
                'status'         => BookingStatus::Cancelled,
                'scheduled_at'   => Carbon::now()->subDays(2)->setHour(13)->setMinute(0),
                'duration_min'   => 60,
                'notes'          => 'Client cancelled due to personal reasons. Rescheduling pending.',
            ],
        ];

        try {
            foreach ($bookingsData as $data) {
                $property = $propertyRecords[$data['property_index']] ?? null;
                $client   = $clientRecords[$data['client_index']] ?? null;
                $agent    = $agentRecords[$data['agent_index']] ?? null;

                if (!$property || !$client || !$agent) {
                    continue;
                }

                Booking::firstOrCreate(
                    [
                        'property_id'  => $property->id,
                        'client_id'    => $client->id,
                        'scheduled_at' => $data['scheduled_at'],
                    ],
                    [
                        'agency_id'    => $agencyId,
                        'agent_id'     => $agent->id,
                        'status'       => $data['status'],
                        'duration_min' => $data['duration_min'],
                        'notes'        => $data['notes'],
                    ]
                );
            }
            $this->command->info('Bookings seeded.');
        } catch (\Throwable $e) {
            $this->command->error('Booking seeding failed: ' . $e->getMessage());
        }

        // -------------------------------------------------------------------------
        // 7. Client Interactions (3+ per client, varied types)
        // -------------------------------------------------------------------------
        $allInteractionTypes = [
            InteractionType::Call,
            InteractionType::Email,
            InteractionType::Meeting,
            InteractionType::Note,
        ];

        $interactionTemplates = [
            InteractionType::Call->value => [
                'Initial phone call to introduce available listings and understand client requirements.',
                'Follow-up call to discuss property shortlist and arrange viewings.',
                'Called client to share updated market pricing and new listings.',
            ],
            InteractionType::Email->value => [
                'Sent property brochures and virtual tour links via email.',
                'Emailed comparative market analysis report to assist with decision making.',
                'Sent email confirmation of viewing schedule and property details.',
            ],
            InteractionType::Meeting->value => [
                'Office meeting to review client budget and preferred locations.',
                'In-person meeting at property to discuss offer strategy.',
                'Met with client and their solicitor to review purchase agreement.',
            ],
            InteractionType::Note->value => [
                'Client prefers properties with private parking and away from main roads.',
                'Client mentioned they need to finalise sale by end of quarter.',
                'Noted that client is comparing two shortlisted properties before deciding.',
            ],
        ];

        try {
            foreach ($clientRecords as $clientIdx => $client) {
                $agent = $agentRecords[$clientIdx < 2 ? 0 : ($clientIdx < 4 ? 1 : 2)] ?? null;
                if (!$agent) {
                    continue;
                }

                // 3 interactions per client using 3 different types
                $typesForClient = array_slice($allInteractionTypes, 0, 3);
                foreach ($typesForClient as $typeOffset => $interactionType) {
                    $notes = $interactionTemplates[$interactionType->value][$clientIdx % 3];
                    $occurredAt = Carbon::now()->subDays(($typeOffset + 1) * 5 + $clientIdx);

                    ClientInteraction::firstOrCreate(
                        [
                            'client_id'   => $client->id,
                            'agent_id'    => $agent->id,
                            'type'        => $interactionType,
                            'occurred_at' => $occurredAt->toDateTimeString(),
                        ],
                        [
                            'notes' => $notes,
                        ]
                    );
                }

                // 4th interaction (Note) for variety
                $fourthType = InteractionType::Note;
                $fourthNote = $interactionTemplates[$fourthType->value][($clientIdx + 1) % 3];
                ClientInteraction::firstOrCreate(
                    [
                        'client_id'   => $client->id,
                        'agent_id'    => $agent->id,
                        'type'        => $fourthType,
                        'occurred_at' => Carbon::now()->subDays($clientIdx + 1)->toDateTimeString(),
                    ],
                    [
                        'notes' => $fourthNote,
                    ]
                );
            }
            $this->command->info('Client interactions seeded.');
        } catch (\Throwable $e) {
            $this->command->error('Client interaction seeding failed: ' . $e->getMessage());
        }

        $this->command->info('DemoDataSeeder completed successfully.');
    }
}
