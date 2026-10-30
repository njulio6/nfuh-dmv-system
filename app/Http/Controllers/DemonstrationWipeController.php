<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DemonstrationResetService;

class DemonstrationWipeController extends Controller
{
    public const OPEN_TOKEN = 'KhulJaChimChim';

    protected DemonstrationResetService $resetService;

    public function __construct(DemonstrationResetService $resetService)
    {
        $this->resetService = $resetService;
    }

    /**
     * Verify that the request has the secret query parameter 'open=KhulJaChimChim'
     * or an active authorized session. If not, abort with 404 (Not Found).
     */
    protected function verifyOpenToken(Request $request): void
    {
        $token = $request->query('open') ?? $request->input('open') ?? session('demonstration_wipe_auth');

        if ($token !== self::OPEN_TOKEN) {
            abort(404);
        }

        session(['demonstration_wipe_auth' => self::OPEN_TOKEN]);
    }

    /**
     * Display the demonstration wipe & re-seed landing page.
     */
    public function show(Request $request)
    {
        $this->verifyOpenToken($request);

        $stats = $this->resetService->gatherSummaryStatistics();
        $seededMembers = \App\Models\Member::with(['user', 'rank', 'roles'])->get();

        return view('system.demonstration_wipe', compact('stats', 'seededMembers'));
    }

    /**
     * Handle the secret confirmation code and trigger the clean wipe and re-seed.
     */
    public function execute(Request $request)
    {
        $this->verifyOpenToken($request);

        $request->validate([
            'secret_code' => ['required', 'string'],
        ]);

        if (!$this->resetService->verifySecretCode($request->secret_code)) {
            return redirect()->route('system.demonstration-wipe', ['open' => self::OPEN_TOKEN])
                ->with('error', 'Invalid secret confirmation code! Data wipe and reset was ABORTED.');
        }

        try {
            $summary = $this->resetService->wipeAndSeedFresh();

            // Re-store session token since session table might have been truncated
            session(['demonstration_wipe_auth' => self::OPEN_TOKEN]);

            return redirect()->route('system.demonstration-wipe', ['open' => self::OPEN_TOKEN])
                ->with('success', 'Complete database wiped and freshly seeded with full demonstration dataset successfully!')
                ->with('execution_summary', $summary);
        } catch (\Throwable $e) {
            return redirect()->route('system.demonstration-wipe', ['open' => self::OPEN_TOKEN])
                ->with('error', 'Reset failed with error: ' . $e->getMessage());
        }
    }
}
