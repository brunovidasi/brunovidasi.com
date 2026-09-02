/**
 * Copyright 2016 Google Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

// DO NOT EDIT THIS GENERATED OUTPUT DIRECTLY!
// This file should be overwritten as part of your build process.
// If you need to extend the behavior of the generated service worker, the best approach is to write
// additional code and include it using the importScripts option:
//   https://github.com/GoogleChrome/sw-precache#importscripts-arraystring
//
// Alternatively, it's possible to make changes to the underlying template file and then use that as the
// new base for generating output, via the templateFilePath option:
//   https://github.com/GoogleChrome/sw-precache#templatefilepath-string
//
// If you go that route, make sure that whenever you update your sw-precache dependency, you reconcile any
// changes made to this original template file with your modified copy.

// This generated service worker JavaScript will precache your site's resources.
// The code needs to be saved in a .js file at the top-level of your site, and registered
// from your pages in order to be used. See
// https://github.com/googlechrome/sw-precache/blob/master/demo/app/js/service-worker-registration.js
// for an example of how you can register this script and handle various service worker events.

/* eslint-env worker, serviceworker */
/* eslint-disable indent, no-unused-vars, no-multiple-empty-lines, max-nested-callbacks, space-before-function-paren, quotes, comma-spacing */
'use strict';

var precacheConfig = [["country.json","846774bb0d782a06447b8b7ef85f8472"],["fonts/OpNCnoEOns3V7GcOrg7-hCJ1.woff2","85ffbb107cf91155193957488422473b"],["fonts/OpNCnoEOns3V7GcPrg7-hCJ1Zhw.woff2","eb80fff27bbcb2d65a264a2b7a2536c4"],["fonts/S6uyw4BMUTPHjx4wXiWtFCc.woff2","129179c4eeb1d784d3d3ad95e0b35905"],["fonts/S6uyw4BMUTPHjxAwXiWtFCfQ7A.woff2","4c6f253240e0c2884b6e64b21b19b06a"],["img/backgrounds/img_0.jpg","860a5ffbd0da58bd518da43073351a38"],["img/backgrounds/img_1.jpg","667659807dbe36137a613be6843b85d5"],["img/backgrounds/img_10.jpg","f1c1bd24a402a603d679bbe8836deb04"],["img/backgrounds/img_11.jpg","78809a374971a6f328908a91462122f2"],["img/backgrounds/img_12.jpg","dd4b130b5b762592abc2b97af60c028b"],["img/backgrounds/img_13.jpg","d8fc5cb210f7374cd232bd5c7f090e9a"],["img/backgrounds/img_14.jpg","1ab468f7adf4dddffcbc233e89928efb"],["img/backgrounds/img_15.jpg","56c65b44d5974af8cbb83f3af38ad6f6"],["img/backgrounds/img_16.jpg","9ecbe248d26365911022d3eb7f6f61db"],["img/backgrounds/img_17.jpg","cb4d57472e0f88aee2fd76af2a7e16df"],["img/backgrounds/img_18.jpg","947897cd5bd96cb040606147b09b05f3"],["img/backgrounds/img_19.jpg","c8495121116692bd000ad672c8ad3d66"],["img/backgrounds/img_2.jpg","d9ffff5b84b7d517c02b285b7f8107ab"],["img/backgrounds/img_20.jpg","b2b53bd9b42ce787091273039900ce60"],["img/backgrounds/img_21.jpg","6cb9ff4a444fc3b57e7d88b4f996ed0f"],["img/backgrounds/img_22.jpg","fd801e06a9fe475ac484df29958984d0"],["img/backgrounds/img_23.jpg","0ae06393fb5f67b9aa8d130590fdc8fc"],["img/backgrounds/img_24.jpg","501790cb81bf6b79a2ad2e1a7f781749"],["img/backgrounds/img_25.jpg","d4f88907c5765b18dede46cb30eaa85d"],["img/backgrounds/img_26.jpg","7240f80e6f276044d27b49dfffe10209"],["img/backgrounds/img_27.jpg","3883c357636db2ea932e32d19a99d881"],["img/backgrounds/img_28.jpg","09262795caf94ea6196d2b243e81112b"],["img/backgrounds/img_29.jpg","2d94d444a72a2b6ea3d8265a60bb4ddf"],["img/backgrounds/img_3.jpg","8939d23aa0b39eb9f8fe01612e229b9b"],["img/backgrounds/img_30.jpg","5642b332d9bc4c5d04f205a40596131d"],["img/backgrounds/img_31.jpg","769f4eb8f5a5aa5110aaa125ce1f657b"],["img/backgrounds/img_32.jpg","f6be167267856772ca9ac1dbbff1ffe8"],["img/backgrounds/img_33.jpg","37d3999e0b237e1a23490448aedc7c21"],["img/backgrounds/img_34.jpg","c4ed6d7f2456a5250d417e20e23f3ded"],["img/backgrounds/img_35.jpg","e0efc3d06bd155e398b7fdf20ec87162"],["img/backgrounds/img_36.jpg","2bdcf8b28d85b2ed37d5f1ab3331f865"],["img/backgrounds/img_37.jpg","86f190f07a380dfc1397fe8d683ed10b"],["img/backgrounds/img_38.jpg","39cd50258a2016132f70f1fd0896d032"],["img/backgrounds/img_39.jpg","0c5c4782667c84c027644adb49323c55"],["img/backgrounds/img_4.jpg","ca8b13eea2a0b1b54bb258f8f4a56ce9"],["img/backgrounds/img_40.jpg","7ffc4922ef9c483960b43d2da10e2a76"],["img/backgrounds/img_41.jpg","9cfc48234648e49cb8b6ca92485606b8"],["img/backgrounds/img_42.jpg","b5c39de1d0063f29dade505031f2763e"],["img/backgrounds/img_43.jpg","63269a4a67770b750d238850a3c236f6"],["img/backgrounds/img_44.jpg","f155d3019d7c2743e38a951675be8fcb"],["img/backgrounds/img_45.jpg","d96cbbd599ee569d025a53fed45c552a"],["img/backgrounds/img_46.jpg","fbfd80ad06e95607326b643c23fb55d2"],["img/backgrounds/img_47.jpg","8ace27d2a84ac412f0b5dc5d2e16e3f0"],["img/backgrounds/img_48.jpg","8ab3a764085d58bc569326e41b376f00"],["img/backgrounds/img_49.jpg","ddaf711d040679191b5dfcc11ff19f78"],["img/backgrounds/img_5.jpg","37dffa545e462a22e4d89a32ccadc50e"],["img/backgrounds/img_50.jpg","94a01cc715d46be2882b1bdb314f72e4"],["img/backgrounds/img_51.jpg","b0e24f5878df8f95689906d779470c2c"],["img/backgrounds/img_52.jpg","a363a1e39bd9aba7754ab75460573a8c"],["img/backgrounds/img_53.jpg","ba1275b83cedfa77bdb502f39f6e1f3a"],["img/backgrounds/img_54.jpg","0b57bcbbc896e6a781d1c5cbce748e23"],["img/backgrounds/img_55.jpg","e3d0d73f81c658d84d2e0bd7e4dc6f46"],["img/backgrounds/img_56.jpg","c83139a0bed3fa19a6704e45ee32ee3b"],["img/backgrounds/img_57.jpg","db247f793effc04599de01f7540604b9"],["img/backgrounds/img_58.jpg","6828dfe494ad107515aed8f080c36b51"],["img/backgrounds/img_59.jpg","bbf542b44188f15e106b297de818fec0"],["img/backgrounds/img_6.jpg","09f1cdc6ad619fd0fb51b25ac8134dbb"],["img/backgrounds/img_60.jpg","05442e32f64c20be4aca6d739795b8c0"],["img/backgrounds/img_61.jpg","51e387da8ee279cf51fa736865b3a124"],["img/backgrounds/img_62.jpg","ce341ad9f215c43b181554df6fe86ea2"],["img/backgrounds/img_7.jpg","3e9df872a7062f669cb297e4efb2f682"],["img/backgrounds/img_8.jpg","b01e86881fb90a82f1265fc233a0ee1d"],["img/backgrounds/img_9.jpg","1913aac3c18d4104951b5c88c1da221f"],["img/flags/am.png","1e5990edca405b37500f0ca254396ed6"],["img/flags/ar.png","a16b821eb170ad74960ecf8e582d15c2"],["img/flags/at.png","663c37727e93059b20e02b4dbaeb84e1"],["img/flags/au.png","d3346770e53605e4bc26b9ae93aa827e"],["img/flags/bb.png","3e185982ae97de6d23645d56911cc9e0"],["img/flags/bd.png","8ecbc6c8c9d4458d006d98acf6b4a599"],["img/flags/be.png","6ef70d83d2c083871f6df6a3b64095ee"],["img/flags/bg.png","57be683802c72c96551630e76e219c7f"],["img/flags/bo.png","f45ccf28cca8e6c8112f6ec06a9eed94"],["img/flags/br.png","631951c4082aed994209d6645bce375e"],["img/flags/bs.png","2f7a77a2c5480f9ceb917ab3c7cfe39a"],["img/flags/bw.png","d4e1d791a3747b621e5101bb14cab840"],["img/flags/ca.png","2311edbf3bb2eee40453cff091e868fa"],["img/flags/ch.png","55bd0eac5465c016a7614c7ee9b7840a"],["img/flags/cl.png","da6466db6c7acf3869a85ee7bfc7dfce"],["img/flags/cn.png","1dcd4452dbb84010e7b31164fd024e95"],["img/flags/co.png","db80a0b2c27a14c6acd558e956db089f"],["img/flags/cy.png","77002868b6c684c31ebaf21dd9b5b07e"],["img/flags/cz.png","8f9fa899e8c6fc279f11fe67eb98f793"],["img/flags/de.png","1177f2ecdfce330f6dd86e9b0da9245d"],["img/flags/dk.png","76312108ffb7ffc007c19f71d15e89b6"],["img/flags/dz.png","e4e5e95e2d2d72f0991f8763b4327a60"],["img/flags/ec.png","1e22b09f9191aa0245aaa46237edc248"],["img/flags/ee.png","0aaa52b367c04889182ba36ddb543be1"],["img/flags/es.png","62197af508b43e461772c215e734fcd0"],["img/flags/fi.png","a66775b9c6cfd64e49bc6a035cf030b0"],["img/flags/fj.png","7a88c6194dc06f6582f63ff40453abe8"],["img/flags/fr.png","332330cf408db33294d79e32d93f67bb"],["img/flags/gh.png","72a8ff54ea005aefce4d5630ca00b120"],["img/flags/gr.png","695b5d02ce268879ff7b213562b28b81"],["img/flags/gy.png","805dff903c1d67933649dcbeacbfa60e"],["img/flags/hk.png","4d511dc9c65048b982fb91f5690382b1"],["img/flags/hn.png","7da13e63e1854475ef6ae35e96633c5b"],["img/flags/hr.png","9ed4b11c6cdd8833be3a54fed74936d6"],["img/flags/hu.png","727d74490e41a203da5f189e753141fd"],["img/flags/id.png","824f286d560a4709eb14cdd3a9735788"],["img/flags/ie.png","5269073e883b4b38f4c5dc8939e8ea25"],["img/flags/il.png","a60a9bdf807d93eae1a295030859e03d"],["img/flags/in.png","2293b647f9afff36849b151ec0230283"],["img/flags/ir.png","ce6b5127184ee96193b20458576d468d"],["img/flags/is.png","14832419caf755d92ef109b07d280ffa"],["img/flags/it.png","24647fbea2fba5f9300ee9c67edda198"],["img/flags/jm.png","68993eef304ada0a41a13dbd6156b529"],["img/flags/jp.png","7ec31b71a81344951ddf97040adb283d"],["img/flags/ke.png","df06d5f449bcafef6681727510e2b6be"],["img/flags/kh.png","11d9996dfa5f1b9d477a4c17f8804a4f"],["img/flags/kr.png","2ca68d64e952761042191ea7ca520025"],["img/flags/lb.png","ac6f2ab3e63a750441a0de4028f17090"],["img/flags/lk.png","617f766211432815e3c00789f019559a"],["img/flags/lr.png","d6aca9525305a5b3af942aeaedb7c209"],["img/flags/lt.png","acb7a3ef3e6aa0a4b354fb66c1e995da"],["img/flags/lu.png","ab277a512e11cf97e4170498a1f3e480"],["img/flags/lv.png","b943e51ed592564dbce57e08b19fcb56"],["img/flags/ma.png","55a84bef39fcf6ae739ec293d35da2bb"],["img/flags/md.png","487471cf1fd154d12c2835c2189d2946"],["img/flags/mn.png","617a95692fd80a00df82374e5e545856"],["img/flags/mu.png","c71f9755d45dd7f4bd617592c286d5a8"],["img/flags/my.png","df70ba5fcc3010dfd5e41aec9f2ac20d"],["img/flags/na.png","888daec438cbc8a99e0f06c5f06e7243"],["img/flags/ng.png","fe7a98f448820458de0e92d63fef89ad"],["img/flags/nl.png","49cb4906567758c01defc26d8f4aa9bd"],["img/flags/no.png","798d13ab6e6ce48b63332e8475de00c2"],["img/flags/np.png","3ca2deb790b2a521a33f0562e6d52eac"],["img/flags/nz.png","148cb3ab39a2ffa9aca6a1ac405e783d"],["img/flags/pe.png","ed07beeb067c580635fb0e74a12bd578"],["img/flags/pg.png","c90cb6d6c0b3a6fca7896e9e2906a6da"],["img/flags/ph.png","aa9964f432a147ab17e5810be85e6997"],["img/flags/pk.png","15d31f37869e155f98fea8273a497289"],["img/flags/pl.png","e97b55016961b6153264e6911c26dcc8"],["img/flags/pt.png","77646b9819fdf2129666ecf2384628db"],["img/flags/ro.png","ca7ef1a358ac7ec55bf1620e3ee777f6"],["img/flags/rs.png","9cca02d0b641c4bbcd259052e920f32f"],["img/flags/ru.png","e4f2bfd387d28209b4ceed17f6f5bd24"],["img/flags/se.png","721087351bd2b112bb715167161de068"],["img/flags/sg.png","0e417ad81dab38a8db1e6c0c53de5307"],["img/flags/si.png","2cfcab6bc0dd161c65857488591e3a0d"],["img/flags/th.png","2fec877cb48da812d49ec9ab7d4382d7"],["img/flags/tr.png","144328861f9076daa2b918749eff674c"],["img/flags/tw.png","02c37f2c4911c30cb6609ed513278a3b"],["img/flags/ua.png","4ac826aea7f01c57e1a69e7c3f24295c"],["img/flags/uk.png","3d8c45e6c04346ac04962648d4059854"],["img/flags/us.png","257c29d74e94391d87a3a66bebd92855"],["img/flags/uy.png","b1dc31882864f51e3ee31985200e2020"],["img/flags/ve.png","8db90f9cd13a1cb6664db9075228f6ee"],["img/flags/ws.png","fdd4428a8496499878dbe9122f99acaf"],["img/flags/za.png","b220ade8c9e978ac31068ed939795470"],["img/flags/zw.png","6a137a68e4f7beac5d4d32e38dededcf"],["img/globe-icon-192.png","c8ca80a601dc14f1256efc97c20a5148"],["img/globe-icon.png","a9c9b36fcd07945a6516039437ab2f8f"],["img/icon.ico","aeaab008b34d69533b366e14eaa0f566"],["index.html","fa034d9defb1b04dd44163cec2131fde"],["js/bootstrap-select.min.js","fc34ce1c5622e906a7c3ef0ec552d2ec"],["js/bootstrap.min.js","c5b5b2fa19bd66ff23211d9f844e0131"],["js/jquery-2.2.2.min.js","1d35678c5edbb639ab7aa5cce0856f57"],["js/js.cookie.js","78695e0755da857435baae96799861f1"],["js/script.js","53bad1ae5cedf4149d60bebb92879933"],["manifest.json","cf87b1be3cfadefd556bc1754d17f860"],["privacy-policy.html","be98b339a316addf8b3b62759ebb76eb"],["style/bootstrap-select.min.css","8d822ef31079b5999fa7bb8ea58038f2"],["style/bootstrap.min.css","2f624089c65f12185e79925bc5a7fc42"],["style/privacy-policy.css","e84f0887260bdb93912d9730bd83f90c"],["style/style.css","e5cd6f1bfcb926b5e13a7be5679d57cc"]];
var cacheName = 'sw-precache-v3-sw-precache-' + (self.registration ? self.registration.scope : '');


var ignoreUrlParametersMatching = [/^utm_/];



var addDirectoryIndex = function(originalUrl, index) {
    var url = new URL(originalUrl);
    if (url.pathname.slice(-1) === '/') {
      url.pathname += index;
    }
    return url.toString();
  };

var cleanResponse = function(originalResponse) {
    // If this is not a redirected response, then we don't have to do anything.
    if (!originalResponse.redirected) {
      return Promise.resolve(originalResponse);
    }

    // Firefox 50 and below doesn't support the Response.body stream, so we may
    // need to read the entire body to memory as a Blob.
    var bodyPromise = 'body' in originalResponse ?
      Promise.resolve(originalResponse.body) :
      originalResponse.blob();

    return bodyPromise.then(function(body) {
      // new Response() is happy when passed either a stream or a Blob.
      return new Response(body, {
        headers: originalResponse.headers,
        status: originalResponse.status,
        statusText: originalResponse.statusText
      });
    });
  };

var createCacheKey = function(originalUrl, paramName, paramValue,
                           dontCacheBustUrlsMatching) {
    // Create a new URL object to avoid modifying originalUrl.
    var url = new URL(originalUrl);

    // If dontCacheBustUrlsMatching is not set, or if we don't have a match,
    // then add in the extra cache-busting URL parameter.
    if (!dontCacheBustUrlsMatching ||
        !(url.pathname.match(dontCacheBustUrlsMatching))) {
      url.search += (url.search ? '&' : '') +
        encodeURIComponent(paramName) + '=' + encodeURIComponent(paramValue);
    }

    return url.toString();
  };

var isPathWhitelisted = function(whitelist, absoluteUrlString) {
    // If the whitelist is empty, then consider all URLs to be whitelisted.
    if (whitelist.length === 0) {
      return true;
    }

    // Otherwise compare each path regex to the path of the URL passed in.
    var path = (new URL(absoluteUrlString)).pathname;
    return whitelist.some(function(whitelistedPathRegex) {
      return path.match(whitelistedPathRegex);
    });
  };

var stripIgnoredUrlParameters = function(originalUrl,
    ignoreUrlParametersMatching) {
    var url = new URL(originalUrl);
    // Remove the hash; see https://github.com/GoogleChrome/sw-precache/issues/290
    url.hash = '';

    url.search = url.search.slice(1) // Exclude initial '?'
      .split('&') // Split into an array of 'key=value' strings
      .map(function(kv) {
        return kv.split('='); // Split each 'key=value' string into a [key, value] array
      })
      .filter(function(kv) {
        return ignoreUrlParametersMatching.every(function(ignoredRegex) {
          return !ignoredRegex.test(kv[0]); // Return true iff the key doesn't match any of the regexes.
        });
      })
      .map(function(kv) {
        return kv.join('='); // Join each [key, value] array into a 'key=value' string
      })
      .join('&'); // Join the array of 'key=value' strings into a string with '&' in between each

    return url.toString();
  };


var hashParamName = '_sw-precache';
var urlsToCacheKeys = new Map(
  precacheConfig.map(function(item) {
    var relativeUrl = item[0];
    var hash = item[1];
    var absoluteUrl = new URL(relativeUrl, self.location);
    var cacheKey = createCacheKey(absoluteUrl, hashParamName, hash, false);
    return [absoluteUrl.toString(), cacheKey];
  })
);

function setOfCachedUrls(cache) {
  return cache.keys().then(function(requests) {
    return requests.map(function(request) {
      return request.url;
    });
  }).then(function(urls) {
    return new Set(urls);
  });
}

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(cacheName).then(function(cache) {
      return setOfCachedUrls(cache).then(function(cachedUrls) {
        return Promise.all(
          Array.from(urlsToCacheKeys.values()).map(function(cacheKey) {
            // If we don't have a key matching url in the cache already, add it.
            if (!cachedUrls.has(cacheKey)) {
              var request = new Request(cacheKey, {credentials: 'same-origin'});
              return fetch(request).then(function(response) {
                // Bail out of installation unless we get back a 200 OK for
                // every request.
                if (!response.ok) {
                  throw new Error('Request for ' + cacheKey + ' returned a ' +
                    'response with status ' + response.status);
                }

                return cleanResponse(response).then(function(responseToCache) {
                  return cache.put(cacheKey, responseToCache);
                });
              });
            }
          })
        );
      });
    }).then(function() {
      
      // Force the SW to transition from installing -> active state
      return self.skipWaiting();
      
    })
  );
});

self.addEventListener('activate', function(event) {
  var setOfExpectedUrls = new Set(urlsToCacheKeys.values());

  event.waitUntil(
    caches.open(cacheName).then(function(cache) {
      return cache.keys().then(function(existingRequests) {
        return Promise.all(
          existingRequests.map(function(existingRequest) {
            if (!setOfExpectedUrls.has(existingRequest.url)) {
              return cache.delete(existingRequest);
            }
          })
        );
      });
    }).then(function() {
      
      return self.clients.claim();
      
    })
  );
});


self.addEventListener('fetch', function(event) {
  if (event.request.method === 'GET') {
    // Should we call event.respondWith() inside this fetch event handler?
    // This needs to be determined synchronously, which will give other fetch
    // handlers a chance to handle the request if need be.
    var shouldRespond;

    // First, remove all the ignored parameters and hash fragment, and see if we
    // have that URL in our cache. If so, great! shouldRespond will be true.
    var url = stripIgnoredUrlParameters(event.request.url, ignoreUrlParametersMatching);
    shouldRespond = urlsToCacheKeys.has(url);

    // If shouldRespond is false, check again, this time with 'index.html'
    // (or whatever the directoryIndex option is set to) at the end.
    var directoryIndex = 'index.html';
    if (!shouldRespond && directoryIndex) {
      url = addDirectoryIndex(url, directoryIndex);
      shouldRespond = urlsToCacheKeys.has(url);
    }

    // If shouldRespond is still false, check to see if this is a navigation
    // request, and if so, whether the URL matches navigateFallbackWhitelist.
    var navigateFallback = '';
    if (!shouldRespond &&
        navigateFallback &&
        (event.request.mode === 'navigate') &&
        isPathWhitelisted([], event.request.url)) {
      url = new URL(navigateFallback, self.location).toString();
      shouldRespond = urlsToCacheKeys.has(url);
    }

    // If shouldRespond was set to true at any point, then call
    // event.respondWith(), using the appropriate cache key.
    if (shouldRespond) {
      event.respondWith(
        caches.open(cacheName).then(function(cache) {
          return cache.match(urlsToCacheKeys.get(url)).then(function(response) {
            if (response) {
              return response;
            }
            throw Error('The cached response that was expected is missing.');
          });
        }).catch(function(e) {
          // Fall back to just fetch()ing the request if some unexpected error
          // prevented the cached response from being valid.
          console.warn('Couldn\'t serve response for "%s" from cache: %O', event.request.url, e);
          return fetch(event.request);
        })
      );
    }
  }
});







