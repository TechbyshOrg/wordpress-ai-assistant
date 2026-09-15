export function isAbortError(error) {
    return !!error && (error.name === 'AbortError' || error.code === 20);
}

/**
 * Tracks in-flight generation requests.
 *
 * AbortController.abort() is only called for a request that is still running.
 * The controller is discarded as soon as the request settles so a later
 * generation cannot abort an already-finished fetch (which can reset the
 * HTTP connection and make the next call fail).
 */
export function createGenerationSession() {
    let active = null;

    return {
        start() {
            if (active && active.inFlight) {
                active.inFlight = false;
                active.controller.abort();
            }
            const controller = new AbortController();
            active = { controller, inFlight: true };
            return controller.signal;
        },
        cancel() {
            if (active && active.inFlight) {
                active.inFlight = false;
                active.controller.abort();
            }
            active = null;
        },
        settle(signal) {
            if (!active || active.controller.signal !== signal) {
                return false;
            }
            active.inFlight = false;
            active = null;
            return true;
        },
    };
}
