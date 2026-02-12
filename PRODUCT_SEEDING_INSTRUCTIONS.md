# Product Seeding Instructions

## Step 1: Find Your Vendor IDs

Run this query in phpMyAdmin SQL tab:

```sql
SELECT 
    v.id as vendor_table_id,
    u.id as user_id,
    u.name as vendor_name,
    u.email,
    v.name as business_name
FROM vendors v
INNER JOIN users u ON v.owner_id = u.id
WHERE u.role = 'vendor' AND u.status = 'active';
```

**IMPORTANT:** Use the `vendor_table_id` (from vendors table), NOT the `user_id`!

Example result:
```
vendor_table_id | user_id | vendor_name        | email                  | business_name
1               | 113     | Spice Garden Owner | vendor@ordivo.com      | Spice Garden
2               | 114     | BK Manager         | bk@ordivo.com          | Burger King
```

In this example:
- Replace `113` in the SQL file with `1` (vendor_table_id for Spice Garden)
- Replace `114` in the SQL file with `2` (vendor_table_id for Burger King)

## Step 2: Find Your Category IDs

Run this query:

```sql
SELECT id, name, category_type FROM categories ORDER BY id;
```

Example result:
```
id | name        | category_type
1  | Appetizers  | food
2  | Main Course | food
3  | Sides       | food
4  | Rice        | food
5  | Desserts    | food
6  | Beverages   | food
```

## Step 3: Edit seed_products_fixed.sql

Open `seed_products_fixed.sql` and:

1. **Replace ALL occurrences of `113`** with your first vendor's `vendor_table_id`
2. **Replace ALL occurrences of `114`** with your second vendor's `vendor_table_id`
3. **Verify category IDs** match your database (1-6 should be correct if you haven't changed them)

### Quick Find & Replace:
- Find: `(113,` Replace with: `(YOUR_VENDOR_ID,`
- Find: `(114,` Replace with: `(YOUR_VENDOR_ID,`

## Step 4: Import the SQL File

1. Open phpMyAdmin
2. Select your `ordivo` database
3. Click "Import" tab
4. Choose `seed_products_fixed.sql`
5. Click "Go"

## Step 5: Add Product Images

After importing, you have two options:

### Option A: Use Vendor Dashboard (Recommended)
1. Login as vendor
2. Go to Products page
3. Click Edit button on each product
4. Upload real images

### Option B: Bulk Upload
1. Download food images from:
   - Unsplash.com
   - Pexels.com
   - Foodiesfeed.com
2. Rename images to match the filenames in the SQL (e.g., `butter_chicken.jpg`, `whopper.jpg`)
3. Upload all images to `uploads/images/` folder via FTP or file manager

## Troubleshooting

### Error: "Cannot add or update a child row: a foreign key constraint fails"
**Solution:** You're using the wrong vendor ID. Use `vendor_table_id` from the vendors table, not `user_id` from users table.

### Error: "Unknown column 'is_top_choice'"
**Solution:** Use `seed_products_fixed.sql` instead of `seed_products.sql`

### Error: "Duplicate entry for key 'slug'"
**Solution:** The slug column has a unique constraint. Either:
- Delete existing products first
- Or modify the product names slightly to make them unique

## Need More Products?

The current script has 103 products (53 + 50). If you need more:
1. Copy the INSERT format
2. Add more product lines
3. Make sure to use correct vendor_id and category_id
4. Keep image filenames unique

## Example: Adding One Product

```sql
INSERT INTO `products` (`vendor_id`, `category_id`, `name`, `description`, `price`, `image`, `is_available`, `is_featured`, `created_at`) VALUES
(1, 2, 'Chicken Curry', 'Delicious chicken curry with spices', 250, 'chicken_curry.jpg', 1, 0, NOW());
```

Replace `1` with your actual vendor_table_id!
