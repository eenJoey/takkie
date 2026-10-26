{{--
    Laadscherm zoals vinkie (pulserende 🌿 op de crème achtergrond), maar alleen als laden langer dan 0,3 s duurt.
    Eigen inline CSS, zodat het ook werkt als de stylesheet nog niet binnen is. Verbergen gebeurt in resources/js/splash.js.
--}}
<style>
    .takkie-splash {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--background, #faf6f3);
        opacity: 0;
        pointer-events: none;
        /* na 0,3 s tonen; vangnet: na 10 s altijd weg, ook als JavaScript niet laadt */
        animation: takkie-splash-in 0.2s ease-out 0.3s forwards, takkie-splash-out 0.3s ease-in 10s forwards;
    }
    .takkie-splash.is-hidden {
        display: none;
    }
    .takkie-splash span {
        font-size: 2.25rem;
        line-height: 1;
        animation: takkie-splash-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes takkie-splash-in {
        to { opacity: 1; pointer-events: auto; }
    }
    @keyframes takkie-splash-out {
        to { opacity: 0; pointer-events: none; visibility: hidden; }
    }
    @keyframes takkie-splash-pulse {
        50% { opacity: 0.5; }
    }
    @media (prefers-reduced-motion: reduce) {
        .takkie-splash span { animation: none; }
    }
</style>
<div id="takkie-splash" class="takkie-splash" role="status" aria-label="Laden">
    <span aria-hidden="true">🌿</span>
</div>
