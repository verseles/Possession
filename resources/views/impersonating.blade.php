@if(Auth::check() && Auth::user()->isImpersonating())
    <div
        id="possession-indicator"
        style="position: fixed; bottom: 20px; right: 20px; padding: 15px 20px; background: #dc2626; color: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); z-index: 9999; font-family: system-ui, -apple-system, sans-serif; font-size: 14px; display: flex; align-items: center; gap: 12px;"
    >
        <span>
            <strong>Impersonating:</strong> {{ Auth::user()->name }}
        </span>
        <form action="{{ route('possession.leave') }}" method="POST" style="margin: 0;">
            @csrf
            <button
                type="submit"
                style="background: white; color: #dc2626; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px; transition: opacity 0.2s;"
                onmouseover="this.style.opacity='0.9'"
                onmouseout="this.style.opacity='1'"
            >
                Stop Impersonating
            </button>
        </form>
    </div>
@endif
