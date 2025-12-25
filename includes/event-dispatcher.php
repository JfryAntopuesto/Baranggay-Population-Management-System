<?php
/**
 * Very small event dispatcher (observer-style).
 * Register listeners per event and dispatch payloads.
 */
class EventDispatcher {
    private $listeners = [];

    public function addListener(string $event, callable $listener) {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, array $payload = []) {
        if (!isset($this->listeners[$event])) {
            return;
        }
        foreach ($this->listeners[$event] as $listener) {
            try {
                $listener($payload);
            } catch (\Throwable $e) {
                error_log("Event listener error for {$event}: " . $e->getMessage());
            }
        }
    }
}

// Simple singleton accessor
function get_event_dispatcher(): EventDispatcher {
    static $dispatcher = null;
    if ($dispatcher === null) {
        $dispatcher = new EventDispatcher();
    }
    return $dispatcher;
}
?>
