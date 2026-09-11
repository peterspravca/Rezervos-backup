// PWA Service Worker for volnekreslo.sk

const CACHE_NAME = 'volnekreslo-cache-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/prevadzky.php',
  '/volnekreslologo.png'
];

self.addEventListener('install', event => {
  // Pre-cache core assets
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        // Soft cache, ignore errors for dynamic files
        return cache.addAll(urlsToCache).catch(err => console.log('Cache failed for some items:', err));
      })
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(clients.claim());
});

self.addEventListener('fetch', event => {
  // Pass-through fetch to always get fresh content, but fallback to cache if offline
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});
