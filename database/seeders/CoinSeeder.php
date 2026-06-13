<?php

namespace Database\Seeders;

use App\Models\Coin;
use Illuminate\Database\Seeder;

class CoinSeeder extends Seeder
{
    public function run(): void
    {
        $coins = [
            ['symbol' => 'BTCUSDT',  'base_asset' => 'BTC',  'name' => 'Bitcoin'],
            ['symbol' => 'ETHUSDT',  'base_asset' => 'ETH',  'name' => 'Ethereum'],
            ['symbol' => 'BNBUSDT',  'base_asset' => 'BNB',  'name' => 'BNB'],
            ['symbol' => 'SOLUSDT',  'base_asset' => 'SOL',  'name' => 'Solana'],
            ['symbol' => 'XRPUSDT',  'base_asset' => 'XRP',  'name' => 'XRP'],
            ['symbol' => 'ADAUSDT',  'base_asset' => 'ADA',  'name' => 'Cardano'],
            ['symbol' => 'DOGEUSDT', 'base_asset' => 'DOGE', 'name' => 'Dogecoin'],
            ['symbol' => 'AVAXUSDT', 'base_asset' => 'AVAX', 'name' => 'Avalanche'],
            ['symbol' => 'LINKUSDT', 'base_asset' => 'LINK', 'name' => 'Chainlink'],
            ['symbol' => 'MATICUSDT','base_asset' => 'MATIC','name' => 'Polygon'],
            ['symbol' => 'DOTUSDT',  'base_asset' => 'DOT',  'name' => 'Polkadot'],
            ['symbol' => 'LTCUSDT',  'base_asset' => 'LTC',  'name' => 'Litecoin'],
            ['symbol' => 'UNIUSDT',  'base_asset' => 'UNI',  'name' => 'Uniswap'],
            ['symbol' => 'ATOMUSDT', 'base_asset' => 'ATOM', 'name' => 'Cosmos'],
            ['symbol' => 'NEARUSDT', 'base_asset' => 'NEAR', 'name' => 'NEAR Protocol'],
            ['symbol' => 'APTUSDT',  'base_asset' => 'APT',  'name' => 'Aptos'],
            ['symbol' => 'ARBUSDT',  'base_asset' => 'ARB',  'name' => 'Arbitrum'],
            ['symbol' => 'OPUSDT',   'base_asset' => 'OP',   'name' => 'Optimism'],
            ['symbol' => 'SHIBUSDT', 'base_asset' => 'SHIB', 'name' => 'Shiba Inu'],
            ['symbol' => 'SUIUSDT',  'base_asset' => 'SUI',  'name' => 'Sui'],
        ];

        foreach ($coins as $coin) {
            Coin::updateOrCreate(
                ['symbol' => $coin['symbol']],
                array_merge($coin, [
                    'quote_asset' => 'USDT',
                    'is_active'   => true,
                ])
            );
        }

        $this->command->info('Seeded ' . count($coins) . ' coins.');
    }
}
