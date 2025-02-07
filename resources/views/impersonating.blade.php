@if(Auth::check() && Auth::user()->isImpersonating())
    <div style="position: fixed; bottom: 20px; right: 20px; padding: 15px; background: #ff4444; color: white; border-radius: 5px;">
        Impersonating {{ Auth::user()->name }}
        <form action="{{ route('possession.leave') }}" method="POST" style="display: inline-block; margin-left: 10px;">
            @csrf
            <button type="submit" style="background: white; color: #ff4444; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                Stop Impersonating
            </button>
        </form>
    </div>
@endif