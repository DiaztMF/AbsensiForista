self.addEventListener("push", function (event) {
    if (!event.data) return;

    const payload = event.data.json();
    const options = {
        body: payload.body || "",
        icon: payload.icon || "/icons/icon-192x192.png",
        badge: payload.badge || "/icons/badge-72x72.png",
        data: payload.data || { url: "/dashboard" },
    };

    event.waitUntil(
        self.registration.showNotification(payload.title || "Pengingat FORISTA", options),
    );
});

self.addEventListener("notificationclick", function (event) {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || "/dashboard";

    event.waitUntil(
        clients.matchAll({ type: "window", includeUncontrolled: true }).then(function (clientList) {
            for (const client of clientList) {
                if (client.url.includes(targetUrl) && "focus" in client) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        }),
    );
});
