import{u as n,C as o,D as e}from"./vue.esm-bundler.js";function t(n){return!!o()&&(e(n),!0)}function i(o){return"function"==typeof o?o():n(o)}const r="undefined"!=typeof window&&"undefined"!=typeof document;"undefined"!=typeof WorkerGlobalScope&&(globalThis,WorkerGlobalScope);const u=Object.prototype.toString,s=n=>"[object Object]"===u.call(n),a=()=>{},l=c();function c(){var n,o;return r&&(null==(n=null==window?void 0:window.navigator)?void 0:n.userAgent)&&(/iP(?:ad|hone|od)/.test(window.navigator.userAgent)||(null==(o=null==window?void 0:window.navigator)?void 0:o.maxTouchPoints)>2&&/iPad|Macintosh/.test(null==window?void 0:window.navigator.userAgent))}function d(n,o=200,e={}){return function(n,o){return function(...e){return new Promise(((t,i)=>{Promise.resolve(n((()=>o.apply(this,e)),{fn:o,thisArg:this,args:e})).then(t).catch(i)}))}}(function(n,o={}){let e,t,r=a;const u=n=>{clearTimeout(n),r(),r=a};return s=>{const a=i(n),l=i(o.maxWait);return e&&u(e),a<=0||void 0!==l&&l<=0?(t&&(u(t),t=null),Promise.resolve(s())):new Promise(((n,i)=>{r=o.rejectOnCancel?i:n,l&&!t&&(t=setTimeout((()=>{e&&u(e),t=null,n(s())}),l)),e=setTimeout((()=>{t&&u(t),t=null,n(s())}),a)}))}}(o,e),n)}export{s as a,l as b,t as c,r as i,a as n,i as t,d as u};
