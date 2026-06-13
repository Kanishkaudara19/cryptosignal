<?php

namespace App\Http\Controllers;

use App\Models\IndicatorCache;
use App\Services\IndicatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    private IndicatorService $indicators;

    public function __construct(IndicatorService $indicators)
    {
        $this->indicators = $indicators;
    }

    /**
     * GET /api/indicators/{symbol}?interval=15m&force=0
     *
     * Returns all indicator values for the symbol + interval.
     * Uses DB cache unless ?force=1 is passed.
     */
    public function show(string $symbol, Request $request): JsonResponse
    {
        $request->validate([
            'interval' => 'sometimes|in:1m,5m,15m,1h,4h,1d,1w',
            'force'    => 'sometimes|boolean',
        ]);

        $interval = $request->input('interval', '15m');
        $force    = (bool) $request->input('force', false);

        try {
            $result = $this->indicators->calculate(
                strtoupper($symbol),
                $interval,
                $force
            );

            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }
}