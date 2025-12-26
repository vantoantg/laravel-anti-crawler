<?php

namespace VanToanTG\AntiCrawler\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VanToanTG\AntiCrawler\Models\BotDetectionLog;
use VanToanTG\AntiCrawler\Models\BlockedIp;
use VanToanTG\AntiCrawler\Models\IpWhitelist;
use VanToanTG\AntiCrawler\Models\CaptchaChallenge;
use Illuminate\Support\Facades\DB;

class BotProtectionController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        $stats = [
            'total_logs' => BotDetectionLog::count(),
            'blocked_today' => BotDetectionLog::where('action_taken', 'blocked')
                ->whereDate('created_at', today())
                ->count(),
            'high_risk_today' => BotDetectionLog::highRisk(70)
                ->whereDate('created_at', today())
                ->count(),
            'active_blocks' => BlockedIp::active()->count(),
            'whitelisted_ips' => IpWhitelist::count(),
            'captcha_challenges_today' => CaptchaChallenge::whereDate('created_at', today())->count(),
            'captcha_success_rate' => $this->getCaptchaSuccessRate(),
        ];

        $recentLogs = BotDetectionLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $topOffenders = BotDetectionLog::select('ip_address', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('ip_address')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return view('anti-crawler::admin.index', compact('stats', 'recentLogs', 'topOffenders'));
    }

    /**
     * Display detection logs
     */
    public function logs(Request $request)
    {
        $query = BotDetectionLog::query();

        // Apply filters
        if ($request->filled('ip')) {
            $query->byIp($request->ip);
        }

        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        if ($request->filled('min_risk')) {
            $query->where('risk_score', '>=', $request->min_risk);
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('anti-crawler::admin.logs', compact('logs'));
    }

    /**
     * Display blocked IPs
     */
    public function blockedIps()
    {
        $blockedIps = BlockedIp::orderBy('created_at', 'desc')->paginate(50);

        return view('anti-crawler::admin.blocked-ips', compact('blockedIps'));
    }

    /**
     * Block an IP address
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:500',
            'duration' => 'nullable|integer|min:1',
        ]);

        $expiresAt = $request->duration 
            ? now()->addMinutes($request->duration) 
            : null;

        BlockedIp::blockIp(
            $request->ip_address,
            $request->reason,
            'manual',
            auth()->id(),
            $expiresAt
        );

        return redirect()->back()->with('success', 'IP address blocked successfully.');
    }

    /**
     * Unblock an IP address
     */
    public function unblockIp($id)
    {
        $blockedIp = BlockedIp::findOrFail($id);
        $blockedIp->delete();

        return redirect()->back()->with('success', 'IP address unblocked successfully.');
    }

    /**
     * Display whitelist
     */
    public function whitelist()
    {
        $whitelist = IpWhitelist::orderBy('created_at', 'desc')->paginate(50);

        return view('anti-crawler::admin.whitelist', compact('whitelist'));
    }

    /**
     * Add IP to whitelist
     */
    public function addToWhitelist(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'description' => 'nullable|string|max:255',
        ]);

        IpWhitelist::addIp(
            $request->ip_address,
            $request->description,
            auth()->id()
        );

        return redirect()->back()->with('success', 'IP address added to whitelist.');
    }

    /**
     * Remove IP from whitelist
     */
    public function removeFromWhitelist($id)
    {
        $whitelist = IpWhitelist::findOrFail($id);
        $whitelist->delete();

        return redirect()->back()->with('success', 'IP address removed from whitelist.');
    }

    /**
     * Get statistics (API endpoint)
     */
    public function stats(Request $request)
    {
        $days = $request->get('days', 7);

        $dailyStats = BotDetectionLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when action_taken = "blocked" then 1 else 0 end) as blocked'),
                DB::raw('sum(case when action_taken = "challenged" then 1 else 0 end) as challenged')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $actionDistribution = BotDetectionLog::select('action_taken', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('action_taken')
            ->get();

        return response()->json([
            'daily_stats' => $dailyStats,
            'action_distribution' => $actionDistribution,
        ]);
    }

    /**
     * Calculate CAPTCHA success rate
     */
    protected function getCaptchaSuccessRate(): float
    {
        $total = CaptchaChallenge::whereDate('created_at', today())->count();
        
        if ($total === 0) {
            return 0;
        }

        $solved = CaptchaChallenge::whereDate('created_at', today())
            ->where('is_solved', true)
            ->count();

        return round(($solved / $total) * 100, 2);
    }
}
