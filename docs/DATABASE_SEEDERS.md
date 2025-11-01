# Database Seeders Guide

## Overview

This project includes comprehensive seeders to populate your database with sample data for development and testing.

## Quick Start

### Fresh Installation

```bash
# Migrate and seed in one command
php artisan migrate:fresh --seed
```

### Seed Only

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=CustomerSeeder
```

## Seeders Overview

### 1. System Configuration

#### PlanSeeder
- Creates subscription plans (1-month, 3-month, 6-month, 12-month)
- Defines pricing and durations

#### BettingTypeSeeder
- Creates all betting types (Lô, Đầu, Đuôi, Xiên, Đá thẳng, etc.)
- Includes syntax aliases for parsing

#### StationSeeder
- Creates lottery stations for all 3 regions
- Includes main stations and sub-stations

#### LotteryScheduleSeeder
- Creates lottery schedules
- Maps which stations draw on which days

#### BettingRateSeeder
- Creates global default betting rates
- Sets buy_rate and payout for each bet type

### 2. Users & Customers

#### UserSeeder
**Creates:**
- 1 Admin user: `admin@keki.com / password`
- 1 Demo user: `user@keki.com / password` (with active subscription)
- 3 Additional users

**Generated:**
- ~5 users total
- Admin has full access
- Demo user has active subscription

#### CustomerSeeder
**Creates:**
- 5-10 customers per user
- Vietnamese names and phone numbers
- **Betting rates stored in JSON column** (optimized!)

**Data generated:**
- ~30-50 customers total
- 90% active status
- Custom betting rates per customer (buy_rate: 0.85-1.0)
- Complete rate configuration for all bet types and regions

**JSON Structure:**
```json
{
  "nam:bao_lo:d2": {"buy_rate": 0.95, "payout": 80},
  "nam:dau": {"buy_rate": 0.95, "payout": 70},
  "bac:xien:x2": {"buy_rate": 0.90, "payout": 15}
}
```

### 3. Lottery Data

#### LotteryResultSeeder
**Creates:**
- 30 days of lottery results (yesterday back to 30 days ago)
- Results for all 3 regions (Bắc, Trung, Nam)
- Realistic prize structure

**Generated:**
- ~100-150 lottery results
- Miền Bắc: 1 result per day (Hà Nội)
- Miền Nam: 3-4 results per day (various stations)
- Miền Trung: 2-3 results per day (various stations)

**Prize Structure:**
- Giải ĐB: 5-6 digits
- Giải 1-8: Appropriate number of prizes
- Random numbers matching Vietnam lottery format

### 4. Betting Tickets

#### BettingTicketSeeder
**Creates:**
- 14 days of betting tickets (2 weeks)
- 2-5 tickets per day per user
- Various betting messages

**Generated:**
- ~100-200 betting tickets
- Mix of regions and stations
- Sample betting messages:
  - `tn 12 34 56 78 lo10n`
  - `hcm 01 23 45 d20n`
  - `mb 10 20 30 xi2 5n`
- Bet amounts: 50,000 - 500,000 VND
- Status: pending/confirmed

## Data Summary

After running all seeders, you'll have:

| Model            | Count (Approx) |
|------------------|----------------|
| Users            | 5              |
| Customers        | 30-50          |
| Betting Types    | 12             |
| Betting Rates    | ~100           |
| Lottery Results  | 100-150        |
| Betting Tickets  | 100-200        |
| Plans            | 4              |
| **Total Records**| **~450-500**   |

## Usage Examples

### Reset and Reseed

```bash
# WARNING: This will delete all data!
php artisan migrate:fresh --seed
```

### Seed Only New Data

```bash
# Add more customers to existing users
php artisan db:seed --class=CustomerSeeder

# Add more lottery results
php artisan db:seed --class=LotteryResultSeeder

# Add more betting tickets
php artisan db:seed --class=BettingTicketSeeder
```

### Custom Seeding

```bash
# Run specific seeders in order
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=LotteryResultSeeder
```

## Demo Accounts

After seeding, you can login with:

### Admin Account
- **Email:** admin@keki.com
- **Password:** password
- **Role:** admin
- **Access:** Full system access

### User Account
- **Email:** user@keki.com
- **Password:** password
- **Role:** user
- **Subscription:** Active (1-month plan)
- **Customers:** 5-10 sample customers
- **Tickets:** ~50-70 betting tickets

### Additional Users
- user1@keki.com / password
- user2@keki.com / password
- user3@keki.com / password (inactive)

## Testing Workflow

1. **Fresh Start:**
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Login as User:**
   - Email: user@keki.com
   - Password: password

3. **Test Features:**
   - View customers with betting rates
   - Create new betting tickets
   - View lottery results
   - Test settlement calculations

4. **Login as Admin:**
   - Email: admin@keki.com
   - Password: password

5. **Admin Features:**
   - Manage users
   - View all tickets
   - Configure system settings

## Optimization Features

### JSON Column for Betting Rates

CustomerSeeder uses the **optimized JSON column** approach:

✅ **Benefits:**
- 97% fewer database records
- 10x faster queries
- 95% smaller indexes
- Easy to scale to 100K+ customers

📊 **Comparison:**
- Old way: 34 records per customer
- New way: 1 JSON field per customer
- 1,000 customers: 34,000 rows → 1,000 rows

### Realistic Data

- Vietnamese names (using Faker vi_VN)
- Vietnamese phone numbers (090, 093, 097, etc.)
- Realistic betting amounts
- Proper lottery number formats
- Region-appropriate stations and schedules

## Customization

### Adjust Number of Customers

Edit `CustomerSeeder.php`:

```php
// Change this line (default: 5-10 per user)
$customerCount = rand(5, 10);

// To create more:
$customerCount = rand(10, 20);
```

### Adjust Date Range for Results

Edit `LotteryResultSeeder.php`:

```php
// Default: 30 days
$startDate = Carbon::today()->subDays(30);

// For 90 days:
$startDate = Carbon::today()->subDays(90);
```

### Adjust Ticket Count

Edit `BettingTicketSeeder.php`:

```php
// Default: 2-5 tickets per day
$ticketCount = rand(2, 5);

// For more tickets:
$ticketCount = rand(5, 10);
```

## Troubleshooting

### Error: "No users found"

**Solution:** Run UserSeeder first
```bash
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CustomerSeeder
```

### Error: "Integrity constraint violation"

**Solution:** Check foreign key dependencies. Run seeders in order:
```bash
php artisan migrate:fresh --seed
```

### Slow Seeding

**Note:** Seeding 30 days of lottery results + betting tickets can take 30-60 seconds. This is normal.

**Speed it up:**
- Reduce date range in seeders
- Use fewer tickets per day
- Disable query logging during seeding

### Memory Issues

**Solution:** Increase PHP memory limit
```bash
php -d memory_limit=512M artisan db:seed
```

## Production Warning

⚠️ **NEVER run seeders in production!**

Seeders are for development only:
- They create demo/test data
- They may override existing data
- They use weak passwords

For production, use migrations only:
```bash
php artisan migrate
```

## Best Practices

1. ✅ Always use `migrate:fresh --seed` for clean start
2. ✅ Test with realistic data volumes
3. ✅ Review generated data before going live
4. ✅ Keep seeders updated with schema changes
5. ✅ Use factories for more complex data generation

## Related Documentation

- [Betting Rates Optimization](../BETTING_RATES_OPTIMIZATION.md)
- [Optimization Comparison](./OPTIMIZATION_COMPARISON.md)
- [Settlement Formulas](../SETTLEMENT_FORMULAS.md)
