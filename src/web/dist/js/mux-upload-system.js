var t,e,i,s,a,r=Object.defineProperty,l=(t,e,i)=>((t,e,i)=>e in t?r(t,e,{enumerable:!0,configurable:!0,writable:!0,value:i}):t[e]=i)(t,"symbol"!=typeof e?e+"":e,i);import{c as n}from"./upchunk.js";
/**
 * @license
 * Copyright 2019 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const o=globalThis,d=o.ShadowRoot&&(void 0===o.ShadyCSS||o.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,u=Symbol(),c=new WeakMap;let h=class{constructor(t,e,i){if(this._$cssResult$=!0,i!==u)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=t,this.t=e}get styleSheet(){let t=this.o;const e=this.t;if(d&&void 0===t){const i=void 0!==e&&1===e.length;i&&(t=c.get(e)),void 0===t&&((this.o=t=new CSSStyleSheet).replaceSync(this.cssText),i&&c.set(e,t))}return t}toString(){return this.cssText}};const p=d?t=>t:t=>t instanceof CSSStyleSheet?(t=>{let e="";for(const i of t.cssRules)e+=i.cssText;return(t=>new h("string"==typeof t?t:t+"",void 0,u))(e)})(t):t,{is:m,defineProperty:_,getOwnPropertyDescriptor:v,getOwnPropertyNames:g,getOwnPropertySymbols:f,getPrototypeOf:x}=Object,w=globalThis,b=w.trustedTypes,y=b?b.emptyScript:"",k=w.reactiveElementPolyfillSupport,C=(t,e)=>t,A={toAttribute(t,e){switch(e){case Boolean:t=t?y:null;break;case Object:case Array:t=null==t?t:JSON.stringify(t)}return t},fromAttribute(t,e){let i=t;switch(e){case Boolean:i=null!==t;break;case Number:i=null===t?null:Number(t);break;case Object:case Array:try{i=JSON.parse(t)}catch(s){i=null}}return i}},E=(t,e)=>!m(t,e),z={attribute:!0,type:String,converter:A,reflect:!1,useDefault:!1,hasChanged:E};
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */Symbol.metadata??(Symbol.metadata=Symbol("metadata")),w.litPropertyMetadata??(w.litPropertyMetadata=new WeakMap);let S=class extends HTMLElement{static addInitializer(t){this._$Ei(),(this.l??(this.l=[])).push(t)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(t,e=z){if(e.state&&(e.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(t)&&((e=Object.create(e)).wrapped=!0),this.elementProperties.set(t,e),!e.noAccessor){const i=Symbol(),s=this.getPropertyDescriptor(t,i,e);void 0!==s&&_(this.prototype,t,s)}}static getPropertyDescriptor(t,e,i){const{get:s,set:a}=v(this.prototype,t)??{get(){return this[e]},set(t){this[e]=t}};return{get:s,set(e){const r=null==s?void 0:s.call(this);null==a||a.call(this,e),this.requestUpdate(t,r,i)},configurable:!0,enumerable:!0}}static getPropertyOptions(t){return this.elementProperties.get(t)??z}static _$Ei(){if(this.hasOwnProperty(C("elementProperties")))return;const t=x(this);t.finalize(),void 0!==t.l&&(this.l=[...t.l]),this.elementProperties=new Map(t.elementProperties)}static finalize(){if(this.hasOwnProperty(C("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(C("properties"))){const t=this.properties,e=[...g(t),...f(t)];for(const i of e)this.createProperty(i,t[i])}const t=this[Symbol.metadata];if(null!==t){const e=litPropertyMetadata.get(t);if(void 0!==e)for(const[t,i]of e)this.elementProperties.set(t,i)}this._$Eh=new Map;for(const[e,i]of this.elementProperties){const t=this._$Eu(e,i);void 0!==t&&this._$Eh.set(t,e)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(t){const e=[];if(Array.isArray(t)){const i=new Set(t.flat(1/0).reverse());for(const t of i)e.unshift(p(t))}else void 0!==t&&e.push(p(t));return e}static _$Eu(t,e){const i=e.attribute;return!1===i?void 0:"string"==typeof i?i:"string"==typeof t?t.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){var t;this._$ES=new Promise(t=>this.enableUpdating=t),this._$AL=new Map,this._$E_(),this.requestUpdate(),null==(t=this.constructor.l)||t.forEach(t=>t(this))}addController(t){var e;(this._$EO??(this._$EO=new Set)).add(t),void 0!==this.renderRoot&&this.isConnected&&(null==(e=t.hostConnected)||e.call(t))}removeController(t){var e;null==(e=this._$EO)||e.delete(t)}_$E_(){const t=new Map,e=this.constructor.elementProperties;for(const i of e.keys())this.hasOwnProperty(i)&&(t.set(i,this[i]),delete this[i]);t.size>0&&(this._$Ep=t)}createRenderRoot(){const t=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return((t,e)=>{if(d)t.adoptedStyleSheets=e.map(t=>t instanceof CSSStyleSheet?t:t.styleSheet);else for(const i of e){const e=document.createElement("style"),s=o.litNonce;void 0!==s&&e.setAttribute("nonce",s),e.textContent=i.cssText,t.appendChild(e)}})(t,this.constructor.elementStyles),t}connectedCallback(){var t;this.renderRoot??(this.renderRoot=this.createRenderRoot()),this.enableUpdating(!0),null==(t=this._$EO)||t.forEach(t=>{var e;return null==(e=t.hostConnected)?void 0:e.call(t)})}enableUpdating(t){}disconnectedCallback(){var t;null==(t=this._$EO)||t.forEach(t=>{var e;return null==(e=t.hostDisconnected)?void 0:e.call(t)})}attributeChangedCallback(t,e,i){this._$AK(t,i)}_$ET(t,e){var i;const s=this.constructor.elementProperties.get(t),a=this.constructor._$Eu(t,s);if(void 0!==a&&!0===s.reflect){const r=(void 0!==(null==(i=s.converter)?void 0:i.toAttribute)?s.converter:A).toAttribute(e,s.type);this._$Em=t,null==r?this.removeAttribute(a):this.setAttribute(a,r),this._$Em=null}}_$AK(t,e){var i,s;const a=this.constructor,r=a._$Eh.get(t);if(void 0!==r&&this._$Em!==r){const t=a.getPropertyOptions(r),l="function"==typeof t.converter?{fromAttribute:t.converter}:void 0!==(null==(i=t.converter)?void 0:i.fromAttribute)?t.converter:A;this._$Em=r;const n=l.fromAttribute(e,t.type);this[r]=n??(null==(s=this._$Ej)?void 0:s.get(r))??n,this._$Em=null}}requestUpdate(t,e,i,s=!1,a){var r;if(void 0!==t){const l=this.constructor;if(!1===s&&(a=this[t]),i??(i=l.getPropertyOptions(t)),!((i.hasChanged??E)(a,e)||i.useDefault&&i.reflect&&a===(null==(r=this._$Ej)?void 0:r.get(t))&&!this.hasAttribute(l._$Eu(t,i))))return;this.C(t,e,i)}!1===this.isUpdatePending&&(this._$ES=this._$EP())}C(t,e,{useDefault:i,reflect:s,wrapped:a},r){i&&!(this._$Ej??(this._$Ej=new Map)).has(t)&&(this._$Ej.set(t,r??e??this[t]),!0!==a||void 0!==r)||(this._$AL.has(t)||(this.hasUpdated||i||(e=void 0),this._$AL.set(t,e)),!0===s&&this._$Em!==t&&(this._$Eq??(this._$Eq=new Set)).add(t))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(e){Promise.reject(e)}const t=this.scheduleUpdate();return null!=t&&await t,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){var t;if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??(this.renderRoot=this.createRenderRoot()),this._$Ep){for(const[t,e]of this._$Ep)this[t]=e;this._$Ep=void 0}const t=this.constructor.elementProperties;if(t.size>0)for(const[e,i]of t){const{wrapped:t}=i,s=this[e];!0!==t||this._$AL.has(e)||void 0===s||this.C(e,void 0,i,s)}}let e=!1;const i=this._$AL;try{e=this.shouldUpdate(i),e?(this.willUpdate(i),null==(t=this._$EO)||t.forEach(t=>{var e;return null==(e=t.hostUpdate)?void 0:e.call(t)}),this.update(i)):this._$EM()}catch(s){throw e=!1,this._$EM(),s}e&&this._$AE(i)}willUpdate(t){}_$AE(t){var e;null==(e=this._$EO)||e.forEach(t=>{var e;return null==(e=t.hostUpdated)?void 0:e.call(t)}),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(t)),this.updated(t)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(t){return!0}update(t){this._$Eq&&(this._$Eq=this._$Eq.forEach(t=>this._$ET(t,this[t]))),this._$EM()}updated(t){}firstUpdated(t){}};S.elementStyles=[],S.shadowRootOptions={mode:"open"},S[C("elementProperties")]=new Map,S[C("finalized")]=new Map,null==k||k({ReactiveElement:S}),(w.reactiveElementVersions??(w.reactiveElementVersions=[])).push("2.1.2");
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
const P=globalThis,U=t=>t,T=P.trustedTypes,M=T?T.createPolicy("lit-html",{createHTML:t=>t}):void 0,R="$lit$",I=`lit$${Math.random().toFixed(9).slice(2)}$`,L="?"+I,O=`<${L}>`,H=document,D=()=>H.createComment(""),V=t=>null===t||"object"!=typeof t&&"function"!=typeof t,B=Array.isArray,N="[ \t\n\f\r]",q=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,j=/-->/g,F=/>/g,W=RegExp(`>|${N}(?:([^\\s"'>=/]+)(${N}*=${N}*(?:[^ \t\n\f\r"'\`<>=]|("|')|))|$)`,"g"),G=/'/g,$=/"/g,Q=/^(?:script|style|textarea|title)$/i,K=(tt=1,(t,...e)=>({_$litType$:tt,strings:t,values:e})),J=Symbol.for("lit-noChange"),Z=Symbol.for("lit-nothing"),X=new WeakMap,Y=H.createTreeWalker(H,129);var tt;function et(t,e){if(!B(t)||!t.hasOwnProperty("raw"))throw Error("invalid template strings array");return void 0!==M?M.createHTML(e):e}class it{constructor({strings:t,_$litType$:e},i){let s;this.parts=[];let a=0,r=0;const l=t.length-1,n=this.parts,[o,d]=((t,e)=>{const i=t.length-1,s=[];let a,r=2===e?"<svg>":3===e?"<math>":"",l=q;for(let n=0;n<i;n++){const e=t[n];let i,o,d=-1,u=0;for(;u<e.length&&(l.lastIndex=u,o=l.exec(e),null!==o);)u=l.lastIndex,l===q?"!--"===o[1]?l=j:void 0!==o[1]?l=F:void 0!==o[2]?(Q.test(o[2])&&(a=RegExp("</"+o[2],"g")),l=W):void 0!==o[3]&&(l=W):l===W?">"===o[0]?(l=a??q,d=-1):void 0===o[1]?d=-2:(d=l.lastIndex-o[2].length,i=o[1],l=void 0===o[3]?W:'"'===o[3]?$:G):l===$||l===G?l=W:l===j||l===F?l=q:(l=W,a=void 0);const c=l===W&&t[n+1].startsWith("/>")?" ":"";r+=l===q?e+O:d>=0?(s.push(i),e.slice(0,d)+R+e.slice(d)+I+c):e+I+(-2===d?n:c)}return[et(t,r+(t[i]||"<?>")+(2===e?"</svg>":3===e?"</math>":"")),s]})(t,e);if(this.el=it.createElement(o,i),Y.currentNode=this.el.content,2===e||3===e){const t=this.el.content.firstChild;t.replaceWith(...t.childNodes)}for(;null!==(s=Y.nextNode())&&n.length<l;){if(1===s.nodeType){if(s.hasAttributes())for(const t of s.getAttributeNames())if(t.endsWith(R)){const e=d[r++],i=s.getAttribute(t).split(I),l=/([.?@])?(.*)/.exec(e);n.push({type:1,index:a,name:l[2],strings:i,ctor:"."===l[1]?nt:"?"===l[1]?ot:"@"===l[1]?dt:lt}),s.removeAttribute(t)}else t.startsWith(I)&&(n.push({type:6,index:a}),s.removeAttribute(t));if(Q.test(s.tagName)){const t=s.textContent.split(I),e=t.length-1;if(e>0){s.textContent=T?T.emptyScript:"";for(let i=0;i<e;i++)s.append(t[i],D()),Y.nextNode(),n.push({type:2,index:++a});s.append(t[e],D())}}}else if(8===s.nodeType)if(s.data===L)n.push({type:2,index:a});else{let t=-1;for(;-1!==(t=s.data.indexOf(I,t+1));)n.push({type:7,index:a}),t+=I.length-1}a++}}static createElement(t,e){const i=H.createElement("template");return i.innerHTML=t,i}}function st(t,e,i=t,s){var a,r;if(e===J)return e;let l=void 0!==s?null==(a=i._$Co)?void 0:a[s]:i._$Cl;const n=V(e)?void 0:e._$litDirective$;return(null==l?void 0:l.constructor)!==n&&(null==(r=null==l?void 0:l._$AO)||r.call(l,!1),void 0===n?l=void 0:(l=new n(t),l._$AT(t,i,s)),void 0!==s?(i._$Co??(i._$Co=[]))[s]=l:i._$Cl=l),void 0!==l&&(e=st(t,l._$AS(t,e.values),l,s)),e}class at{constructor(t,e){this._$AV=[],this._$AN=void 0,this._$AD=t,this._$AM=e}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(t){const{el:{content:e},parts:i}=this._$AD,s=((null==t?void 0:t.creationScope)??H).importNode(e,!0);Y.currentNode=s;let a=Y.nextNode(),r=0,l=0,n=i[0];for(;void 0!==n;){if(r===n.index){let e;2===n.type?e=new rt(a,a.nextSibling,this,t):1===n.type?e=new n.ctor(a,n.name,n.strings,this,t):6===n.type&&(e=new ut(a,this,t)),this._$AV.push(e),n=i[++l]}r!==(null==n?void 0:n.index)&&(a=Y.nextNode(),r++)}return Y.currentNode=H,s}p(t){let e=0;for(const i of this._$AV)void 0!==i&&(void 0!==i.strings?(i._$AI(t,i,e),e+=i.strings.length-2):i._$AI(t[e])),e++}}class rt{get _$AU(){var t;return(null==(t=this._$AM)?void 0:t._$AU)??this._$Cv}constructor(t,e,i,s){this.type=2,this._$AH=Z,this._$AN=void 0,this._$AA=t,this._$AB=e,this._$AM=i,this.options=s,this._$Cv=(null==s?void 0:s.isConnected)??!0}get parentNode(){let t=this._$AA.parentNode;const e=this._$AM;return void 0!==e&&11===(null==t?void 0:t.nodeType)&&(t=e.parentNode),t}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(t,e=this){t=st(this,t,e),V(t)?t===Z||null==t||""===t?(this._$AH!==Z&&this._$AR(),this._$AH=Z):t!==this._$AH&&t!==J&&this._(t):void 0!==t._$litType$?this.$(t):void 0!==t.nodeType?this.T(t):(t=>B(t)||"function"==typeof(null==t?void 0:t[Symbol.iterator]))(t)?this.k(t):this._(t)}O(t){return this._$AA.parentNode.insertBefore(t,this._$AB)}T(t){this._$AH!==t&&(this._$AR(),this._$AH=this.O(t))}_(t){this._$AH!==Z&&V(this._$AH)?this._$AA.nextSibling.data=t:this.T(H.createTextNode(t)),this._$AH=t}$(t){var e;const{values:i,_$litType$:s}=t,a="number"==typeof s?this._$AC(t):(void 0===s.el&&(s.el=it.createElement(et(s.h,s.h[0]),this.options)),s);if((null==(e=this._$AH)?void 0:e._$AD)===a)this._$AH.p(i);else{const t=new at(a,this),e=t.u(this.options);t.p(i),this.T(e),this._$AH=t}}_$AC(t){let e=X.get(t.strings);return void 0===e&&X.set(t.strings,e=new it(t)),e}k(t){B(this._$AH)||(this._$AH=[],this._$AR());const e=this._$AH;let i,s=0;for(const a of t)s===e.length?e.push(i=new rt(this.O(D()),this.O(D()),this,this.options)):i=e[s],i._$AI(a),s++;s<e.length&&(this._$AR(i&&i._$AB.nextSibling,s),e.length=s)}_$AR(t=this._$AA.nextSibling,e){var i;for(null==(i=this._$AP)||i.call(this,!1,!0,e);t!==this._$AB;){const e=U(t).nextSibling;U(t).remove(),t=e}}setConnected(t){var e;void 0===this._$AM&&(this._$Cv=t,null==(e=this._$AP)||e.call(this,t))}}class lt{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(t,e,i,s,a){this.type=1,this._$AH=Z,this._$AN=void 0,this.element=t,this.name=e,this._$AM=s,this.options=a,i.length>2||""!==i[0]||""!==i[1]?(this._$AH=Array(i.length-1).fill(new String),this.strings=i):this._$AH=Z}_$AI(t,e=this,i,s){const a=this.strings;let r=!1;if(void 0===a)t=st(this,t,e,0),r=!V(t)||t!==this._$AH&&t!==J,r&&(this._$AH=t);else{const s=t;let l,n;for(t=a[0],l=0;l<a.length-1;l++)n=st(this,s[i+l],e,l),n===J&&(n=this._$AH[l]),r||(r=!V(n)||n!==this._$AH[l]),n===Z?t=Z:t!==Z&&(t+=(n??"")+a[l+1]),this._$AH[l]=n}r&&!s&&this.j(t)}j(t){t===Z?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,t??"")}}class nt extends lt{constructor(){super(...arguments),this.type=3}j(t){this.element[this.name]=t===Z?void 0:t}}class ot extends lt{constructor(){super(...arguments),this.type=4}j(t){this.element.toggleAttribute(this.name,!!t&&t!==Z)}}class dt extends lt{constructor(t,e,i,s,a){super(t,e,i,s,a),this.type=5}_$AI(t,e=this){if((t=st(this,t,e,0)??Z)===J)return;const i=this._$AH,s=t===Z&&i!==Z||t.capture!==i.capture||t.once!==i.once||t.passive!==i.passive,a=t!==Z&&(i===Z||s);s&&this.element.removeEventListener(this.name,this,i),a&&this.element.addEventListener(this.name,this,t),this._$AH=t}handleEvent(t){var e;"function"==typeof this._$AH?this._$AH.call((null==(e=this.options)?void 0:e.host)??this.element,t):this._$AH.handleEvent(t)}}class ut{constructor(t,e,i){this.element=t,this.type=6,this._$AN=void 0,this._$AM=e,this.options=i}get _$AU(){return this._$AM._$AU}_$AI(t){st(this,t)}}const ct=P.litHtmlPolyfillSupport;null==ct||ct(it,rt),(P.litHtmlVersions??(P.litHtmlVersions=[])).push("3.3.3");const ht=globalThis;
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */class pt extends S{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){var t;const e=super.createRenderRoot();return(t=this.renderOptions).renderBefore??(t.renderBefore=e.firstChild),e}update(t){const e=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(t),this._$Do=((t,e,i)=>{const s=(null==i?void 0:i.renderBefore)??e;let a=s._$litPart$;if(void 0===a){const t=(null==i?void 0:i.renderBefore)??null;s._$litPart$=a=new rt(e.insertBefore(D(),t),t,void 0,i??{})}return a._$AI(t),a})(e,this.renderRoot,this.renderOptions)}connectedCallback(){var t;super.connectedCallback(),null==(t=this._$Do)||t.setConnected(!0)}disconnectedCallback(){var t;super.disconnectedCallback(),null==(t=this._$Do)||t.setConnected(!1)}render(){return J}}pt._$litElement$=!0,pt.finalized=!0,null==(t=ht.litElementHydrateSupport)||t.call(ht,{LitElement:pt});const mt=ht.litElementPolyfillSupport;null==mt||mt({LitElement:pt}),(ht.litElementVersions??(ht.litElementVersions=[])).push("4.2.2");const _t="/actions/mux/assets/upload-asset",vt="/actions/mux/assets/get-upload-by-id",gt="/actions/mux/assets/get-asset-by-id",ft="/actions/mux/assets/create",xt="/actions/mux/assets/create-asset-from-url",$t="queued",wt="uploading",bt="paused",yt="complete",kt="failed",Ct="cancelled",At=[];let Et=0;function zt(t,e){const i={...e};return i[window.Craft.csrfTokenName]=window.Craft.csrfTokenValue,fetch(t,{method:"POST",headers:{Accept:"application/json","Content-Type":"application/json"},body:JSON.stringify(i)}).then(t=>{if(!t.ok)throw new Error(`HTTP ${t.status}`);return t.json()})}function St(t,e){document.dispatchEvent(new CustomEvent(t,{detail:e,bubbles:!1}))}function Pt(t,e){t.state=e,St("mux:upload:state-change",{itemId:t.id,state:e,item:Ut(t)}),function(){if(!At.length)return;At.every(t=>t.state===yt||t.state===kt||t.state===Ct)&&St("mux:upload:batch-complete",{items:At.map(Ut)})}()}function Ut(t){return{id:t.id,title:t.title,type:t.type,state:t.state,progress:t.progress,elementId:t.elementId,elementCpUrl:t.elementCpUrl}}async function Tt(t,e,i,s){const a={...t,asset_id:t.id,asset_status:t.status,title:e,volumeUid:i,folderId:s};delete a.id,delete a.status;const r=await zt(ft,a);if(!(null==r?void 0:r.id))throw new Error("CREATE_ASSET returned no element id");return r}function Mt(t){return t?{watermarkEnabled:t.enabled??!1,watermarkUrl:t.url??"",watermarkVerticalAlign:t.verticalAlign??"",watermarkVerticalMargin:t.verticalMargin??"",watermarkHorizontalAlign:t.horizontalAlign??"",watermarkHorizontalMargin:t.horizontalMargin??"",watermarkWidth:t.width??"",watermarkHeight:t.height??"",watermarkOpacity:null!=t.opacityPct?String(t.opacityPct)+"%":""}:{}}async function Rt(t){var e,i,s;let a;Pt(t,wt);try{a=await zt(_t,{title:t.title,volumeUid:t.volumeUid,folderId:t.folderId,normalizeAudio:t.settings.normalizeAudio,autoGenerateCaptions:t.settings.autoGenerateCaptions,captionsLanguage:t.settings.captionsLanguage,playbackPolicy:t.settings.playbackPolicy,videoQuality:t.settings.videoQuality,...Mt(t.settings.watermark)})}catch(o){return void Pt(t,kt)}t.uploadData=a;const r=parseInt(null==(s=null==(i=null==(e=window.RocketPark)?void 0:e.Mux)?void 0:i.Settings)?void 0:s.uploadChunkSize)||30720,l=n({endpoint:a.url,file:t.file,chunkSize:r});t.uploadInstance=l,l.on("progress",e=>{t.progress=e.detail,St("mux:upload:progress",{itemId:t.id,progress:e.detail})}),l.on("error",()=>Pt(t,kt)),l.on("success",()=>async function(t){var e;try{const i=await async function(t,e=12,i=2500){for(let s=0;s<e;s++){const a=await zt(vt,{id:t});if(null==a?void 0:a.asset_id)return a;s<e-1&&await new Promise(t=>setTimeout(t,i))}throw new Error("Timed out waiting for Mux to assign asset_id to upload")}(t.uploadData.id),s=await zt(gt,{id:i.asset_id}),a=await Tt(s,t.title,t.volumeUid,t.folderId);t.elementId=a.id;const r=(null==(e=window.Craft)?void 0:e.cpTrigger)||"admin";t.elementCpUrl=`/${r}/mux/assets/${a.id}`,Pt(t,yt)}catch(o){Pt(t,kt)}}(t))}async function It(t){var e;Pt(t,wt);try{const i=await zt(xt,{url:t.url,title:t.title,volumeUid:t.volumeUid,folderId:t.folderId,normalizeAudio:t.settings.normalizeAudio,autoGenerateCaptions:t.settings.autoGenerateCaptions,captionsLanguage:t.settings.captionsLanguage,playbackPolicy:t.settings.playbackPolicy,videoQuality:t.settings.videoQuality,...Mt(t.settings.watermark)}),s=await Tt(i,t.title,t.volumeUid,t.folderId);t.elementId=s.id;const a=(null==(e=window.Craft)?void 0:e.cpTrigger)||"admin";t.elementCpUrl=`/${a}/mux/assets/${s.id}`,t.progress=100,Pt(t,yt)}catch(i){Pt(t,kt)}}function Lt(t,e,i){const s=function(){var t,e;const i=null==(e=null==(t=window.Craft)?void 0:t.elementIndex)?void 0:e.sourceKey;return(null==i?void 0:i.startsWith("volume:"))?i.replace("volume:",""):null}(),a=(null==(l=null==(r=window.Craft)?void 0:r.elementIndex)?void 0:l.currentFolderId)??null;var r,l;t.forEach(t=>{const r=(Et+=1,`mux-${Et}-${Math.random().toString(36).slice(2,8)}`),l=e.get(t)||("file"===t.type?t.file.name:t.url),n={id:r,type:t.type,file:"file"===t.type?t.file:null,url:"url"===t.type?t.url:null,title:l,settings:i,volumeUid:s,folderId:a,state:$t,progress:0,uploadData:null,uploadInstance:null,elementId:null,elementCpUrl:null};At.push(n),St("mux:upload:start",{item:Ut(n)})}),At.filter(t=>t.state===$t).forEach(t=>"url"===t.type?It(t):Rt(t))}function Ot(t){var e,i;const s=At.find(e=>e.id===t);s&&(null==(i=null==(e=s.uploadInstance)?void 0:e.abort)||i.call(e),Pt(s,Ct))}window.addEventListener("beforeunload",t=>{At.some(t=>t.state===$t||t.state===wt||t.state===bt||t.state===kt)&&(t.preventDefault(),t.returnValue="")});const Ht=(()=>{var t,e,i;const s=null==(i=null==(e=null==(t=window.RocketPark)?void 0:t.Mux)?void 0:e.Settings)?void 0:i.maxUploadFileSize;return s?1024*Number(s):734003200})(),Dt=((null==(s=null==(i=null==(e=window.RocketPark)?void 0:e.Mux)?void 0:i.Settings)?void 0:s.defaultExtensions)||"MP4,MOV,MKV,WEBM,M4V,MP3,M4A,WAV,FLAC,AAC,OGG,OPUS").toLowerCase().split(",").map(t=>t.trim()),Vt=[{value:"",label:"Auto detect language",beta:!1},{value:"en",label:"English",beta:!1},{value:"es",label:"Spanish",beta:!1},{value:"it",label:"Italian",beta:!1},{value:"pt",label:"Portuguese",beta:!1},{value:"de",label:"German",beta:!1},{value:"fr",label:"French",beta:!1},{value:"pl",label:"Polish",beta:!0},{value:"ru",label:"Russian",beta:!0},{value:"nl",label:"Dutch",beta:!0},{value:"ca",label:"Catalan",beta:!0},{value:"tr",label:"Turkish",beta:!0},{value:"sv",label:"Swedish",beta:!0},{value:"uk",label:"Ukrainian",beta:!0},{value:"no",label:"Norwegian",beta:!0},{value:"fi",label:"Finnish",beta:!0},{value:"sk",label:"Slovak",beta:!0},{value:"el",label:"Greek",beta:!0},{value:"cs",label:"Czech",beta:!0},{value:"hr",label:"Croatian",beta:!0},{value:"da",label:"Danish",beta:!0},{value:"ro",label:"Romanian",beta:!0},{value:"bg",label:"Bulgarian",beta:!0}],Bt=["Select","Details","Settings"],Nt=K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,qt=K`<svg class="mux-wizard__dropzone-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M16 28l8-8 8 8"/><path d="M24 20v16"/><rect x="6" y="6" width="36" height="36" rx="4"/></svg>`,jt=K`<svg class="mux-wizard__url-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>`,Ft=K`<svg class="mux-wizard__thumb-placeholder" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="2"/><polygon points="10 8 16 12 10 16 10 8"/></svg>`,Wt=K`<svg class="mux-wizard__thumb-placeholder" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>`,Gt=["mp3","m4a","wav","flac","aac","ogg","opus"],Qt=K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`,Kt=K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>`,Jt=K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><circle cx="12" cy="7.5" r="0.9" fill="currentColor" stroke="none"/></svg>`;class Zt extends pt{createRenderRoot(){return this}constructor(){super(),this._step=1,this._items=[],this._thumbnails=new Map,this._titles=new Map,this._settings={videoQuality:"plus",playbackPolicy:"public",normalizeAudio:!1,autoGenerateCaptions:!0,captionsLanguage:"",watermark:{enabled:!1,url:"",verticalAlign:"top",verticalMargin:"0",horizontalAlign:"left",horizontalMargin:"0",width:"",height:"",opacityPct:75}},this._isDragover=!1,this._dropError="",this._urlValue="",this._urlError="",this._editingItem=null,this._editValue="",this._qualityTipOpen=!1,this._onDocClickForTip=t=>{t.target.closest(".mux-wizard__info-wrap")||(this._qualityTipOpen=!1)}}get _dialog(){return this.querySelector("dialog")}open(){this._reset(),this.updateComplete.then(()=>{var t;return null==(t=this._dialog)?void 0:t.showModal()})}_reset(){this._step=1,this._items=[],this._thumbnails.clear(),this._titles.clear(),this._isDragover=!1,this._dropError="",this._urlValue="",this._urlError="",this._editingItem=null,this._editValue="",this._qualityTipOpen=!1}updated(t){if(t.has("_editingItem")&&null!==this._editingItem){const t=this.querySelector(".mux-wizard__title-input");null==t||t.focus(),null==t||t.select()}t.has("_qualityTipOpen")&&(this._qualityTipOpen?document.addEventListener("click",this._onDocClickForTip,{capture:!0}):document.removeEventListener("click",this._onDocClickForTip,{capture:!0}))}render(){return K`
            <dialog
                class="mux-wizard"
                aria-labelledby="mux-wizard-heading"
                @click=${t=>{t.target===this._dialog&&this._dialog.close()}}
                @close=${()=>this._reset()}
            >
                <div class="mux-wizard__inner">
                    ${this._renderHeader()}
                    ${this._renderSteps()}
                    <div class="mux-wizard__panels">
                        ${1===this._step?this._renderStep1():Z}
                        ${2===this._step?this._renderStep2():Z}
                        ${3===this._step?this._renderStep3():Z}
                    </div>
                    ${this._renderFooter()}
                </div>
            </dialog>`}_renderHeader(){return K`
            <header class="mux-wizard__header">
                <h2 class="mux-wizard__heading" id="mux-wizard-heading">${Craft.t("mux","Upload Files")}</h2>
                <button type="button" class="mux-wizard__close-btn" aria-label="${Craft.t("mux","Cancel")}"
                    @click=${()=>{var t;return null==(t=this._dialog)?void 0:t.close()}}>
                    ${Nt}
                </button>
            </header>`}_renderSteps(){return K`
            <ol class="mux-wizard__steps" aria-label="${Craft.t("mux","Steps")}">
                ${Bt.map((t,e)=>{const i=e+1;return K`
                        <li class="mux-wizard__step ${this._step===i?"is-active":""} ${this._step>i?"is-done":""}"
                            data-step="${i}"
                            aria-current=${this._step===i?"step":null}>
                            <span class="mux-wizard__step-label">${Craft.t("mux",t)}</span>
                            <span class="mux-wizard__step-indicator"></span>
                        </li>`})}
            </ol>`}_renderStep1(){return K`
            <div class="mux-wizard__dropzone ${this._isDragover?"is-dragover":""}"
                role="button" tabindex="0"
                aria-label="${Craft.t("mux","Drop video or audio files here or browse")}"
                @click=${t=>{var e;t.target===t.currentTarget&&(null==(e=this.querySelector(".mux-wizard__file-input"))||e.click())}}
                @keydown=${t=>{var e;"Enter"!==t.key&&" "!==t.key||(t.preventDefault(),null==(e=this.querySelector(".mux-wizard__file-input"))||e.click())}}
                @dragover=${t=>{t.preventDefault(),this._isDragover=!0}}
                @dragleave=${t=>{t.currentTarget.contains(t.relatedTarget)||(this._isDragover=!1)}}
                @drop=${t=>{t.preventDefault(),this._isDragover=!1,t.dataTransfer.files.length&&this._onFilesSelected(Array.from(t.dataTransfer.files))}}>
                <input type="file" multiple accept="video/*,audio/*" class="mux-wizard__file-input" tabindex="-1" aria-hidden="true"
                    @change=${t=>{t.target.files.length&&this._onFilesSelected(Array.from(t.target.files)),t.target.value=""}}>
                ${qt}
                <p class="mux-wizard__dropzone-label">${Craft.t("mux","Drag and drop video or audio files here")}</p>
                <button type="button" class="mux-wizard__browse-btn"
                    @click=${t=>{var e;t.stopPropagation(),null==(e=this.querySelector(".mux-wizard__file-input"))||e.click()}}>
                    ${Craft.t("mux","Upload a local file")}
                </button>
            </div>
            ${this._dropError?K`<p class="mux-wizard__drop-error">${this._dropError}</p>`:Z}

            <div class="mux-wizard__url-section">
                <p class="mux-wizard__url-label">${Craft.t("mux","Upload from a public URL")}</p>
                <div class="mux-wizard__url-row">
                    <input type="url" class="mux-wizard__url-input text" placeholder="https://"
                        aria-label="${Craft.t("mux","Video URL")}"
                        .value=${this._urlValue}
                        @input=${t=>{this._urlValue=t.target.value}}
                        @keydown=${t=>{"Enter"===t.key&&(t.preventDefault(),this._addUrl())}}>
                    <button type="button" class="btn mux-wizard__url-add-btn" @click=${()=>this._addUrl()}>
                        ${Craft.t("mux","Add")}
                    </button>
                </div>
                ${this._urlError?K`<p class="mux-wizard__url-error">${this._urlError}</p>`:Z}
            </div>`}_renderStep2(){return K`
            <div class="mux-wizard__file-list" role="list">
                ${this._items.map(t=>this._renderFileRow(t))}
            </div>`}_renderFileRow(t){const e=this._titles.get(t)||"",i=this._editingItem===t;return K`
            <div class="mux-wizard__file-row" role="listitem">
                <div class="mux-wizard__file-thumb">${this._renderThumb(t)}</div>
                <div class="mux-wizard__file-body">
                    <div class="mux-wizard__file-title-wrap">
                        ${i?K`
                            <input type="text" class="mux-wizard__title-input text"
                                .value=${this._editValue}
                                @input=${t=>{this._editValue=t.target.value}}
                                @blur=${()=>this._commitEdit(t)}
                                @keydown=${e=>{"Enter"===e.key&&(e.preventDefault(),e.target.blur()),"Escape"===e.key&&(this._editValue=this._titles.get(t)||"",e.target.blur())}}>`:K`
                            <span class="mux-wizard__file-title" @click=${()=>this._startEdit(t)}>${e}</span>
                            <button type="button" class="mux-wizard__edit-btn" aria-label="${Craft.t("mux","Edit title")}"
                                @click=${()=>this._startEdit(t)}>${Qt}</button>`}
                    </div>
                </div>
                <button type="button" class="mux-wizard__delete-btn" aria-label="${Craft.t("mux","Remove")}"
                    @click=${()=>this._removeItem(t)}>${Kt}</button>
            </div>`}_renderThumb(t){if("url"===t.type)return jt;const e=this._thumbnails.get(t.file);return e?K`<img src=${e} alt="" class="mux-wizard__thumb-img">`:this._isAudioFile(t.file)?Wt:Ft}_isAudioFile(t){const e=t.name.split(".").pop().toLowerCase();return Gt.includes(e)}_renderStep3(){const t=this._settings;return K`
            <div class="mux-wizard__settings-form">
                <div class="field">
                    <div class="heading">
                        <label for="mux-wiz-quality">${Craft.t("mux","Video Quality")}</label>
                        <span class="mux-wizard__info-wrap">
                            <button type="button" class="mux-wizard__info-btn"
                                aria-label="${Craft.t("mux","More info")}"
                                aria-expanded="${this._qualityTipOpen}"
                                @click=${t=>{t.stopPropagation(),this._qualityTipOpen=!this._qualityTipOpen}}>${Jt}</button>
                            ${this._qualityTipOpen?K`
                                <div class="mux-wizard__info-popover" role="tooltip">
                                    ${Craft.t("mux","Basic is free but has a reduced quality ladder and no live streaming or DRM support. Plus (recommended) uses Mux's per-title encoding at standard quality, billed per minute of video. Premium costs more per minute but is tuned for top-tier content like live sports or studio releases.")}
                                </div>
                            `:Z}
                        </span>
                    </div>
                    <div class="input ltr"><div class="select">
                        <select id="mux-wiz-quality" .value=${t.videoQuality}
                            @change=${t=>this._setSetting("videoQuality",t.target.value)}>
                            <option value="basic" title="${Craft.t("mux","Free encoding. Reduced quality ladder. No live streaming or DRM support.")}">${Craft.t("mux","Basic (free)")}</option>
                            <option value="plus" title="${Craft.t("mux","Mux's recommended default. Standard quality per-title encoding, billed per minute.")}">${Craft.t("mux","Plus (standard, recommended)")}</option>
                            <option value="premium" title="${Craft.t("mux","Highest quality and extended encoding ladder, for premium content. Highest per-minute cost.")}">${Craft.t("mux","Premium (highest quality)")}</option>
                        </select>
                    </div></div>
                </div>

                <div class="field">
                    <div class="heading"><label>${Craft.t("mux","Playback Policy")}</label></div>
                    <div class="input ltr mux-wizard__radio-group">
                        <label class="mux-wizard__radio-label">
                            <input type="radio" name="mux-wiz-policy" value="public"
                                ?checked=${"public"===t.playbackPolicy}
                                @change=${()=>this._setSetting("playbackPolicy","public")}>
                            ${Craft.t("mux","Public")}
                        </label>
                        <label class="mux-wizard__radio-label">
                            <input type="radio" name="mux-wiz-policy" value="signed"
                                ?checked=${"signed"===t.playbackPolicy}
                                @change=${()=>this._setSetting("playbackPolicy","signed")}>
                            ${Craft.t("mux","Signed")}
                        </label>
                    </div>
                </div>

                <div class="field">
                    <div class="heading"><label for="mux-wiz-normalize">${Craft.t("mux","Normalize Audio")}</label></div>
                    <div class="input ltr"><label class="mux-wizard__toggle">
                        <input type="checkbox" id="mux-wiz-normalize"
                            ?checked=${t.normalizeAudio}
                            @change=${t=>this._setSetting("normalizeAudio",t.target.checked)}>
                        <span class="mux-wizard__toggle-track"><span class="mux-wizard__toggle-thumb"></span></span>
                    </label></div>
                </div>

                <div class="field">
                    <div class="heading"><label for="mux-wiz-captions">${Craft.t("mux","Auto-Generate Captions")}</label></div>
                    <div class="input ltr"><label class="mux-wizard__toggle">
                        <input type="checkbox" id="mux-wiz-captions"
                            ?checked=${t.autoGenerateCaptions}
                            @change=${t=>this._setSetting("autoGenerateCaptions",t.target.checked)}>
                        <span class="mux-wizard__toggle-track"><span class="mux-wizard__toggle-thumb"></span></span>
                    </label></div>
                </div>

                ${t.autoGenerateCaptions?K`
                    <div class="field mux-wizard__lang-field">
                        <div class="heading"><label for="mux-wiz-lang">${Craft.t("mux","Caption Language")}</label></div>
                        <div class="input ltr"><div class="select">
                            <select id="mux-wiz-lang" .value=${t.captionsLanguage}
                                @change=${t=>this._setSetting("captionsLanguage",t.target.value)}>
                                ${Vt.map(t=>K`
                                    <option value=${t.value}>
                                        ${t.beta?`${Craft.t("mux",t.label)} (beta)`:Craft.t("mux",t.label)}
                                    </option>`)}
                            </select>
                        </div></div>
                    </div>`:Z}

                ${this._renderWatermarkSection()}
            </div>`}_renderFooter(){const t=3===this._step,e=2===this._step&&0===this._items.length;return K`
            <footer class="mux-wizard__footer">
                ${this._step>1?K`
                    <button type="button" class="btn mux-wizard__back-btn"
                        @click=${()=>{this._step--}}>
                        ${Craft.t("mux","Back")}
                    </button>`:Z}
                <div class="mux-wizard__footer-spacer"></div>
                <button type="button" class="btn submit mux-wizard__next-btn"
                    ?disabled=${e}
                    @click=${()=>t?this._startUploads():this._step++}>
                    ${t?Craft.t("mux","Start Uploads"):Craft.t("mux","Next")}
                </button>
            </footer>`}_renderWatermarkSection(){const t=this._settings.watermark;return K`
            <div class="mux-wizard__watermark-section">
                <div class="mux-wizard__watermark-header">
                    <span class="mux-wizard__watermark-label">${Craft.t("mux","Watermark")}</span>
                    <button type="button" role="switch"
                        class="mux-wizard__toggle-btn ${t.enabled?"is-on":""}"
                        aria-checked=${t.enabled?"true":"false"}
                        @click=${()=>this._setWatermark("enabled",!t.enabled)}>
                        <span class="mux-wizard__toggle-track"><span class="mux-wizard__toggle-thumb"></span></span>
                    </button>
                </div>

                ${t.enabled?K`
                    <div class="mux-wizard__watermark-body">
                        <div class="field mux-wizard__watermark-url-field">
                            <div class="heading"><label class="mux-wizard__section-subheading">${Craft.t("mux","Watermark URL")}</label></div>
                            <div class="input ltr">
                                <input type="url" class="text fullwidth" placeholder="https://"
                                    .value=${t.url}
                                    @input=${t=>this._setWatermark("url",t.target.value)}>
                            </div>
                        </div>

                        <div class="mux-wizard__watermark-grid">
                            <div class="mux-wizard__watermark-panel">
                                <span class="mux-wizard__panel-heading">${Craft.t("mux","Position")}</span>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t("mux","Vertical")}</label></div>
                                    <div class="mux-wizard__align-group" role="group" aria-label="${Craft.t("mux","Vertical alignment")}">
                                        ${[{value:"top",label:"Top",icon:K`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="9" y1="2" x2="9" y2="8"/><rect x="4" y="8" width="10" height="8" rx="1.5"/><line x1="2" y1="2" x2="16" y2="2" stroke-linecap="round"/></svg>`},{value:"middle",label:"Center",icon:K`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="9" y1="2" x2="9" y2="7"/><rect x="4" y="7" width="10" height="4" rx="1"/><line x1="9" y1="11" x2="9" y2="16"/><line x1="2" y1="9" x2="16" y2="9"/></svg>`},{value:"bottom",label:"Bottom",icon:K`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="4" y="2" width="10" height="8" rx="1.5"/><line x1="9" y1="10" x2="9" y2="16"/><line x1="2" y1="16" x2="16" y2="16" stroke-linecap="round"/></svg>`}].map(e=>K`
                                            <button type="button"
                                                class="mux-wizard__align-btn ${t.verticalAlign===e.value?"is-active":""}"
                                                title=${Craft.t("mux",e.label)}
                                                aria-pressed=${t.verticalAlign===e.value?"true":"false"}
                                                @click=${()=>this._setWatermark("verticalAlign",e.value)}>
                                                ${e.icon}
                                            </button>`)}
                                    </div>
                                </div>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t("mux","V. margin")} <span class="mux-wizard__unit">px</span></label></div>
                                    <input type="text" class="text mux-wizard__margin-input"
                                        .value=${t.verticalMargin}
                                        @input=${t=>this._setWatermark("verticalMargin",t.target.value)}>
                                </div>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t("mux","Horizontal")}</label></div>
                                    <div class="mux-wizard__align-group" role="group" aria-label="${Craft.t("mux","Horizontal alignment")}">
                                        ${[{value:"left",label:"Left",icon:K`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="2" y1="2" x2="2" y2="16" stroke-linecap="round"/><line x1="2" y1="9" x2="8" y2="9"/><rect x="8" y="4" width="8" height="10" rx="1.5"/></svg>`},{value:"center",label:"Center",icon:K`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="9" y1="2" x2="9" y2="16"/><rect x="5" y="5" width="8" height="8" rx="1"/></svg>`},{value:"right",label:"Right",icon:K`<svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><line x1="16" y1="2" x2="16" y2="16" stroke-linecap="round"/><line x1="16" y1="9" x2="10" y2="9"/><rect x="2" y="4" width="8" height="10" rx="1.5"/></svg>`}].map(e=>K`
                                            <button type="button"
                                                class="mux-wizard__align-btn ${t.horizontalAlign===e.value?"is-active":""}"
                                                title=${Craft.t("mux",e.label)}
                                                aria-pressed=${t.horizontalAlign===e.value?"true":"false"}
                                                @click=${()=>this._setWatermark("horizontalAlign",e.value)}>
                                                ${e.icon}
                                            </button>`)}
                                    </div>
                                </div>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t("mux","H. margin")} <span class="mux-wizard__unit">px</span></label></div>
                                    <input type="text" class="text mux-wizard__margin-input"
                                        .value=${t.horizontalMargin}
                                        @input=${t=>this._setWatermark("horizontalMargin",t.target.value)}>
                                </div>
                            </div>

                            <div class="mux-wizard__watermark-panel">
                                <span class="mux-wizard__panel-heading">${Craft.t("mux","Size & Appearance")}</span>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t("mux","Width")} <span class="mux-wizard__unit">px</span></label></div>
                                    <input type="text" class="text mux-wizard__size-input"
                                        .value=${t.width}
                                        placeholder="auto"
                                        @input=${t=>this._setWatermark("width",t.target.value)}>
                                </div>

                                <div class="field">
                                    <div class="heading"><label>${Craft.t("mux","Height")} <span class="mux-wizard__unit">px</span></label></div>
                                    <input type="text" class="text mux-wizard__size-input"
                                        .value=${t.height}
                                        placeholder="auto"
                                        @input=${t=>this._setWatermark("height",t.target.value)}>
                                </div>

                                <div class="field">
                                    <div class="heading">
                                        <label>${Craft.t("mux","Opacity")}</label>
                                        <span class="mux-wizard__opacity-value">${t.opacityPct}%</span>
                                    </div>
                                    <div class="mux-wizard__opacity-row">
                                        <input type="range" class="mux-wizard__opacity-slider"
                                            min="0" max="100" step="1"
                                            .value=${String(t.opacityPct)}
                                            @input=${t=>this._setWatermark("opacityPct",Number(t.target.value))}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`:Z}
            </div>`}_setSetting(t,e){this._settings={...this._settings,[t]:e}}_setWatermark(t,e){this._settings={...this._settings,watermark:{...this._settings.watermark,[t]:e}}}_startEdit(t){this._editingItem=t,this._editValue=this._titles.get(t)||""}_commitEdit(t){const e=this._editValue.trim()||("file"===t.type?t.file.name:t.url);this._titles.set(t,e),this._editingItem=null,this.requestUpdate()}_removeItem(t){this._items=this._items.filter(e=>e!==t),this._items.length||(this._step=1)}_onFilesSelected(t){const e=t.filter(t=>this._isValidFile(t)),i=t.filter(t=>!this._isValidFile(t));if(this._dropError=i.length?Craft.t("mux","{n} file(s) skipped: unsupported format or too large.",{n:i.length}):"",!e.length)return;const s=[];e.forEach(t=>{if(!this._items.some(e=>"file"===e.type&&e.file===t)){const e={type:"file",file:t};s.push(e),this._titles.set(e,t.name.replace(/\.[^.]+$/,"")),this._generateThumb(t).then(e=>{this._thumbnails.set(t,e),this.requestUpdate()})}}),s.length&&(this._items=[...this._items,...s],this._step=2)}_addUrl(){const t=this._urlValue.trim();if(!t)return;if(!this._isValidUrl(t))return void(this._urlError=Craft.t("mux","Please enter a valid HTTP or HTTPS URL."));this._urlError="";const e={type:"url",url:t};this._items=[...this._items,e],this._titles.set(e,this._titleFromUrl(t)),this._urlValue="",this._step=2}_startUploads(){var t;this._items.length&&(null==(t=this._dialog)||t.close(),Lt(this._items,this._titles,{...this._settings}))}_isValidUrl(t){try{const e=new URL(t);return"http:"===e.protocol||"https:"===e.protocol}catch(e){return!1}}_titleFromUrl(t){try{const e=new URL(t),i=e.pathname.split("/").filter(Boolean);return(i.at(-1)||"").replace(/\.[^.]+$/,"")||e.hostname||t}catch(e){return t}}_isValidFile(t){const e=t.name.split(".").pop().toLowerCase();return Dt.includes(e)&&t.size<=Ht}_generateThumb(t){return this._isAudioFile(t)?Promise.resolve(null):new Promise(e=>{const i=document.createElement("video"),s=URL.createObjectURL(t);i.src=s,i.preload="metadata",i.muted=!0;const a=()=>URL.revokeObjectURL(s);i.addEventListener("error",()=>{a(),e(null)},{once:!0}),i.addEventListener("loadedmetadata",()=>{i.currentTime=Math.min(1,.1*i.duration)},{once:!0}),i.addEventListener("seeked",()=>{try{const t=document.createElement("canvas");t.width=160,t.height=90,t.getContext("2d").drawImage(i,0,0,160,90),a(),e(t.toDataURL("image/jpeg",.75))}catch(t){a(),e(null)}},{once:!0})})}}l(Zt,"properties",{_step:{state:!0},_items:{state:!0},_settings:{state:!0},_isDragover:{state:!0},_dropError:{state:!0},_urlValue:{state:!0},_urlError:{state:!0},_editingItem:{state:!0},_editValue:{state:!0},_qualityTipOpen:{state:!0}}),customElements.define("mux-upload-wizard",Zt);const Xt={all:null,queued:$t,uploading:wt,uploaded:yt,failed:[kt,Ct]},Yt=[{filter:"all",label:"All"},{filter:"queued",label:"Queued"},{filter:"uploading",label:"Uploading"},{filter:"uploaded",label:"Uploaded"},{filter:"failed",label:"Failed"}],te={[$t]:K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,[wt]:K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>`,[bt]:K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>`,[yt]:K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>`,[kt]:K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,[Ct]:K`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`};class ee extends pt{createRenderRoot(){return this}constructor(){super(),this._items=[],this._activeFilter="all",this._collapsed=!1,this._visible=!1,this._progress=new Map,this._dismissTimer=null,this._handlers=null}connectedCallback(){super.connectedCallback(),this._handlers={start:t=>this._onStart(t.detail.item),progress:t=>this._onProgress(t.detail.itemId,t.detail.progress),stateChange:t=>this._onStateChange(t.detail.itemId,t.detail.item),removed:t=>this._onRemoved(t.detail.itemId),batchComplete:()=>this._onBatchComplete()},document.addEventListener("mux:upload:start",this._handlers.start),document.addEventListener("mux:upload:progress",this._handlers.progress),document.addEventListener("mux:upload:state-change",this._handlers.stateChange),document.addEventListener("mux:upload:removed",this._handlers.removed),document.addEventListener("mux:upload:batch-complete",this._handlers.batchComplete)}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener("mux:upload:start",this._handlers.start),document.removeEventListener("mux:upload:progress",this._handlers.progress),document.removeEventListener("mux:upload:state-change",this._handlers.stateChange),document.removeEventListener("mux:upload:removed",this._handlers.removed),document.removeEventListener("mux:upload:batch-complete",this._handlers.batchComplete),this._dismissTimer&&clearTimeout(this._dismissTimer)}_onStart(t){this._visible=!0,this._dismissTimer&&(clearTimeout(this._dismissTimer),this._dismissTimer=null),this._items=[...this._items,t]}_onProgress(t,e){this._progress.set(t,e),this.requestUpdate()}_onStateChange(t,e){this._items=this._items.map(i=>i.id===t?e:i)}_onRemoved(t){this._progress.delete(t),this._items=this._items.filter(e=>e.id!==t)}_onBatchComplete(){var t;(null==(t=window.Craft)?void 0:t.elementIndex)&&window.Craft.elementIndex.updateElements(!0);!this._items.some(t=>t.state===kt||t.state===Ct)&&this._items.length>0&&(this._dismissTimer=setTimeout(()=>this._dismiss(),3e3))}get _filteredItems(){const t=Xt[this._activeFilter];return null===t?this._items:Array.isArray(t)?this._items.filter(e=>t.includes(e.state)):this._items.filter(e=>e.state===t)}get _counts(){return{all:this._items.length,queued:this._items.filter(t=>t.state===$t).length,uploading:this._items.filter(t=>t.state===wt||t.state===bt).length,uploaded:this._items.filter(t=>t.state===yt).length,failed:this._items.filter(t=>t.state===kt||t.state===Ct).length}}get _allSettled(){return this._items.length>0&&this._items.every(t=>t.state===yt||t.state===kt||t.state===Ct)}render(){if(!this._visible)return Z;const t=this._counts;return K`
            <div class="mux-tray ${this._collapsed?"is-collapsed":""}"
                role="region"
                aria-label="${Craft.t("mux","Upload progress")}">
                <header class="mux-tray__header">
                    <button type="button" class="mux-tray__collapse-btn"
                        aria-label="${Craft.t("mux","Toggle uploads tray")}"
                        @click=${()=>{this._collapsed=!this._collapsed}}>
                        <svg class="mux-tray__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <span class="mux-tray__title">${Craft.t("mux","Uploads")}</span>
                    <span class="mux-tray__badge" aria-live="polite">${t.all}</span>
                    ${this._allSettled?K`<button type="button" class="mux-tray__dismiss-btn"
                                aria-label="${Craft.t("mux","Dismiss")}"
                                @click=${()=>this._dismiss()}>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>`:Z}
                </header>

                ${this._collapsed?Z:K`
                    <div class="mux-tray__body">
                        <div class="mux-tray__tabs" role="tablist" aria-label="${Craft.t("mux","Filter uploads")}">
                            ${Yt.map(e=>K`
                                <button role="tab"
                                    aria-selected=${this._activeFilter===e.filter?"true":"false"}
                                    class="mux-tray__tab ${this._activeFilter===e.filter?"is-active":""}"
                                    data-filter=${e.filter}
                                    @click=${()=>{this._activeFilter=e.filter}}>
                                    ${Craft.t("mux",e.label)}
                                    <span class="mux-tray__tab-count">${t[e.filter]}</span>
                                </button>`)}
                        </div>
                        <div class="mux-tray__items" role="list">
                            ${this._filteredItems.map(t=>this._renderItem(t))}
                        </div>
                    </div>`}
            </div>`}_renderItem(t){const e=this._progress.get(t.id)??0,i=t.state===wt||t.state===bt;return K`
            <div class="mux-tray__item" role="listitem" data-item-id=${t.id} data-state=${t.state}>
                <div class="mux-tray__item-icon" aria-hidden="true">
                    ${te[t.state]??Z}
                </div>
                <div class="mux-tray__item-body">
                    ${t.state===yt&&t.elementCpUrl?K`<a href=${t.elementCpUrl} class="mux-tray__item-title-link">${t.title}</a>`:K`<span class="mux-tray__item-title">${t.title}</span>`}
                    ${i?K`
                        <div class="mux-tray__progress-row">
                            <div class="mux-tray__progress-track">
                                <div class="mux-tray__progress-bar" style="--mux-progress: ${e}%"></div>
                            </div>
                            <span class="mux-tray__progress-label">${Math.round(e)}%</span>
                        </div>`:Z}
                </div>
                <div class="mux-tray__item-actions">
                    ${this._renderActions(t)}
                </div>
            </div>`}_renderActions(t){const{state:e,id:i,elementCpUrl:s}=t;switch(e){case wt:return K`
                    <button type="button" class="btn small secondary" @click=${()=>function(t){var e;const i=At.find(e=>e.id===t);(null==i?void 0:i.state)===wt&&(null==(e=i.uploadInstance)?void 0:e.pause)&&(i.uploadInstance.pause(),Pt(i,bt))}(i)}>${Craft.t("mux","Pause")}</button>
                    <button type="button" class="btn small secondary" @click=${()=>Ot(i)}>${Craft.t("mux","Cancel")}</button>`;case bt:return K`
                    <button type="button" class="btn small submit" @click=${()=>function(t){var e;const i=At.find(e=>e.id===t);(null==i?void 0:i.state)===bt&&(null==(e=i.uploadInstance)?void 0:e.resume)&&(i.uploadInstance.resume(),Pt(i,wt))}(i)}>${Craft.t("mux","Resume")}</button>
                    <button type="button" class="btn small secondary" @click=${()=>Ot(i)}>${Craft.t("mux","Cancel")}</button>`;case yt:return s?K`<a href=${s} class="btn small submit">${Craft.t("mux","View Asset")}</a>`:Z;case kt:case Ct:return K`
                    <button type="button" class="btn small submit" @click=${()=>function(t){const e=At.find(e=>e.id===t);!e||e.state!==kt&&e.state!==Ct||(e.progress=0,e.uploadInstance=null,e.uploadData=null,"url"===e.type?It(e):Rt(e))}(i)}>${Craft.t("mux","Retry")}</button>
                    <button type="button" class="btn small secondary" @click=${()=>function(t){const e=At.findIndex(e=>e.id===t);-1!==e&&At.splice(e,1),St("mux:upload:removed",{itemId:t})}(i)}>${Craft.t("mux","Remove")}</button>`;default:return Z}}_dismiss(){this._dismissTimer&&(clearTimeout(this._dismissTimer),this._dismissTimer=null),this._visible=!1,this._items=[],this._progress.clear(),this._activeFilter="all",this._collapsed=!1}}l(ee,"properties",{_items:{state:!0},_activeFilter:{state:!0},_collapsed:{state:!0},_visible:{state:!0}}),customElements.define("mux-upload-tray",ee);const ie=document.createElement("mux-upload-wizard"),se=document.createElement("mux-upload-tray");document.body.append(ie,se),null==(a=document.querySelector("#mux-wizard-btn"))||a.addEventListener("click",()=>ie.open()),document.addEventListener("mux:wizard:open",()=>ie.open());
