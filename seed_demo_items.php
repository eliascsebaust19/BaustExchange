<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    // Get first user or create a default demo user if empty
    $user = fetch("SELECT * FROM users LIMIT 1");
    if (!$user) {
        $userId = insert('users', [
            'google_id' => 'demo_google_123',
            'name' => 'Ettesaf Hossen',
            'email' => 'ettesafhossen123@gmail.com',
            'role' => 'student',
            'student_level' => 'junior',
            'department' => 'CSE',
            'student_id' => '0802420405101139',
            'level_term' => 'Level 3 Term I',
            'status' => 'active'
        ]);
    } else {
        $userId = $user['id'];
    }

    // Fetch category IDs
    $categories = fetchAll("SELECT id, slug FROM categories");
    $catMap = [];
    foreach ($categories as $cat) {
        $catMap[$cat['slug']] = $cat['id'];
    }

    $itemsData = [
        [
            'title' => 'Data Structures & Algorithms Made Easy (4th Ed.)',
            'category_slug' => 'books',
            'transaction_type' => 'exchange',
            'price' => 350.00,
            'exchange_for' => 'Discrete Mathematics or Physics Textbook',
            'condition' => 'like_new',
            'location' => 'BAUST CSE Department / Academic Building Level 3',
            'contact_preference' => 'In-app messaging or WhatsApp',
            'description' => 'Excellent condition textbook, clean pages with no pencil markings. Essential for CSE Level 2 & 3 coursework.',
            'image' => 'dsa_book.png'
        ],
        [
            'title' => 'Casio fx-991EX ClassWiz Scientific Calculator',
            'category_slug' => 'electronics',
            'transaction_type' => 'sell',
            'price' => 1200.00,
            'exchange_for' => null,
            'condition' => 'good',
            'location' => 'BAUST Central Library / Campus Canteen',
            'contact_preference' => 'Phone call or direct meet',
            'description' => 'Original Casio scientific calculator with solar dual power. All buttons working crisp and smooth.',
            'image' => 'casio_calculator.png'
        ],
        [
            'title' => 'Touch Dimmable LED Desk Study Lamp',
            'category_slug' => 'stationery',
            'transaction_type' => 'sell',
            'price' => 450.00,
            'exchange_for' => null,
            'condition' => 'like_new',
            'location' => 'BAUST Student Hostel',
            'contact_preference' => 'In-app chat',
            'description' => '3-level touch brightness eye-care desk lamp with built-in rechargeable battery for load-shedding study hours.',
            'image' => 'study_lamp.png'
        ],
        [
            'title' => 'Complete Engineering Drawing & Drafting Kit',
            'category_slug' => 'academic-materials',
            'transaction_type' => 'exchange',
            'price' => 0.00,
            'exchange_for' => 'Scientific Calculator or Lab Notebook',
            'condition' => 'good',
            'location' => 'BAUST ME Department Lab',
            'contact_preference' => 'In-app chat',
            'description' => 'Includes T-Square, set squares, mini-drafter, compass set, and board clips. Perfect for 1st year engineering drawing.',
            'image' => 'drawing_kit.png'
        ],
        [
            'title' => 'Ergonomic Mesh Study Chair for Hostel Room',
            'category_slug' => 'furniture',
            'transaction_type' => 'sell',
            'price' => 1800.00,
            'exchange_for' => null,
            'condition' => 'good',
            'location' => 'BAUST Boys Hostel Building 2',
            'contact_preference' => 'Direct meet at hostel',
            'description' => 'Breathable mesh back chair with lumbar support and pneumatic height lever. Selling because graduating this term.',
            'image' => 'study_chair.png'
        ],
        [
            'title' => 'Canon 4K DSLR Camera Body for Rent',
            'category_slug' => 'electronics',
            'transaction_type' => 'rent',
            'price' => 0.00,
            'rent_price' => 1500.00,
            'rent_period' => 'day',
            'exchange_for' => null,
            'condition' => 'good',
            'location' => 'BAUST Media Club / Central Library',
            'contact_preference' => 'In-app chat or phone',
            'description' => 'Rent a Canon 4K DSLR camera for events, videos, and project shoots. Perfect for EEE Media productions and campus event coverage.',
            'image' => 'dslr_camera.png'
        ],
        [
            'title' => 'Semester Textbooks (Share for Exam Prep)',
            'category_slug' => 'books',
            'transaction_type' => 'share',
            'price' => 0.00,
            'exchange_for' => null,
            'condition' => 'good',
            'location' => 'BAUST CSE Building, Level 2',
            'contact_preference' => 'In-app messaging',
            'description' => 'Sharing my complete set of Level 3 core text books for exam preparation. Free to borrow, just return within a week.',
            'image' => 'textbook_stack.png'
        ]
    ];

    $count = 0;
    foreach ($itemsData as $item) {
        $catId = $catMap[$item['category_slug']] ?? 1;

        // Check if listing already exists to prevent duplicate insertion
        $existing = fetch("SELECT id FROM listings WHERE title = ?", [$item['title']]);
        if ($existing) {
            $listingId = $existing['id'];
        } else {
            $listingId = insert('listings', [
                'user_id' => $userId,
                'category_id' => $catId,
                'title' => $item['title'],
                'description' => $item['description'],
                'condition' => $item['condition'],
                'transaction_type' => $item['transaction_type'],
                'price' => $item['price'],
                'rent_price' => $item['rent_price'] ?? null,
                'rent_period' => $item['rent_period'] ?? null,
                'exchange_for' => $item['exchange_for'],
                'location' => $item['location'],
                'contact_preference' => $item['contact_preference'],
                'status' => 'active',
                'views' => rand(15, 80)
            ]);
            $count++;
        }

        // Insert image if not already present
        $imgExist = fetch("SELECT id FROM listing_images WHERE listing_id = ?", [$listingId]);
        if (!$imgExist) {
            insert('listing_images', [
                'listing_id' => $listingId,
                'image_path' => $item['image']
            ]);
        }
    }

    echo "SUCCESS: Seeded {$count} demo items with images into database.";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
