var KboAutocomplete=(function(e){Object.defineProperty(e,Symbol.toStringTag,{value:`Module`});var t=class extends Error{constructor(e,t,n){super(n),this.name=`ApiError`,this.status=e,this.code=t}};async function n(e,n){let r=new URL(n.endpoint);r.searchParams.set(`q`,e),r.searchParams.set(`lang`,n.lang),r.searchParams.set(`limit`,String(n.limit));let i;try{i=await fetch(r,{headers:{"X-Api-Key":n.key},signal:n.signal})}catch(e){throw e instanceof DOMException&&e.name===`AbortError`?e:new t(0,null,`The Firmatlas API could not be reached.`)}if(!i.ok){let e=null,n=`The Firmatlas API answered ${i.status}.`;try{let t=await i.json();e=t.error?.code??null,t.error?.message&&(n=t.error.message)}catch{}throw new t(i.status,e,n)}let a=await i.json();return Array.isArray(a.data)?a.data:[]}function r(e){let t=e.trim();return/^(be)?[\d.\s-]+$/i.test(t)&&t.replace(/\D/g,``).length>=4}function i(e){return e.trim().length>=3||r(e)}var a={nl:{results:e=>e===1?`1 bedrijf gevonden`:`${e} bedrijven gevonden`,none:`Geen bedrijf gevonden`,credit:`Gegevens: KBO`,label:`Bedrijven`},fr:{results:e=>e===1?`1 entreprise trouvée`:`${e} entreprises trouvées`,none:`Aucune entreprise trouvée`,credit:`Données : BCE`,label:`Entreprises`},de:{results:e=>e===1?`1 Unternehmen gefunden`:`${e} Unternehmen gefunden`,none:`Kein Unternehmen gefunden`,credit:`Daten: ZDU`,label:`Unternehmen`},en:{results:e=>e===1?`1 company found`:`${e} companies found`,none:`No company found`,credit:`Data: KBO`,label:`Companies`}};function o(e){return a[e]}function s(e,t){for(let n of[e,t.documentElement.lang]){let e=(n??``).slice(0,2).toLowerCase();if(e===`nl`||e===`fr`||e===`de`||e===`en`)return e}return`nl`}var c=`.kbo-ac-popup {
  --kbo-bg: #fff;
  --kbo-fg: #111827;
  --kbo-muted: #6b7280;
  --kbo-border: rgba(17, 24, 39, 0.12);
  --kbo-active: #f3f4f6;
  --kbo-radius: 10px;
  --kbo-shadow: 0 10px 30px -10px rgba(17, 24, 39, 0.25), 0 2px 6px rgba(17, 24, 39, 0.06);
  --kbo-font: inherit;
  --kbo-z: 2147483000;
  position: fixed;
  z-index: var(--kbo-z);
  box-sizing: border-box;
  margin: 0;
  padding: 4px;
  background: var(--kbo-bg);
  color: var(--kbo-fg);
  border: 1px solid var(--kbo-border);
  border-radius: var(--kbo-radius);
  box-shadow: var(--kbo-shadow);
  font: 14px/1.35 var(--kbo-font);
  overflow: hidden;
  transform-origin: top center;
  transition:
    opacity 140ms cubic-bezier(0.23, 1, 0.32, 1),
    transform 140ms cubic-bezier(0.23, 1, 0.32, 1);
}
.kbo-ac-popup[data-side="top"] {
  transform-origin: bottom center;
}
.kbo-ac-popup[hidden] {
  display: none;
}
@starting-style {
  .kbo-ac-popup {
    opacity: 0;
    transform: translateY(-2px) scale(0.98);
  }
}
.kbo-ac-list {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: min(320px, 50vh);
  overflow-y: auto;
  overscroll-behavior: contain;
}
.kbo-ac-option {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 1px;
  min-height: 44px;
  padding: 6px 10px;
  border-radius: calc(var(--kbo-radius) - 4px);
  cursor: pointer;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}
.kbo-ac-option[aria-selected="true"] {
  background: var(--kbo-active);
}
.kbo-ac-name {
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.kbo-ac-meta {
  color: var(--kbo-muted);
  font-size: 12.5px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}
.kbo-ac-empty {
  padding: 10px;
  color: var(--kbo-muted);
}
.kbo-ac-credit {
  padding: 4px 10px 2px;
  color: var(--kbo-muted);
  font-size: 11px;
  text-align: right;
}
.kbo-ac-status {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}
@media (prefers-reduced-motion: reduce) {
  .kbo-ac-popup {
    transition: opacity 140ms ease;
  }
  @starting-style {
    .kbo-ac-popup {
      transform: none;
    }
  }
}
@media (prefers-color-scheme: dark) {
  .kbo-ac-popup[data-theme="auto"] {
    --kbo-bg: #18181b;
    --kbo-fg: #f4f4f5;
    --kbo-muted: #a1a1aa;
    --kbo-border: rgba(244, 244, 245, 0.14);
    --kbo-active: #27272a;
  }
}
`,l=150,u=6,d=4,f=0;function p(){let e=new Set;return t=>{e.has(t)||(e.add(t),console.warn(`[firmatlas-autocomplete] ${t}`))}}function m(e){if(e.querySelector(`style[data-kbo-autocomplete]`))return;let t=e.createElement(`style`);t.setAttribute(`data-kbo-autocomplete`,``),t.textContent=c,e.head.append(t)}function h(e,t,n){let r=e.createElement(t);return r.className=n,r}var g={nl:`bus`,fr:`boîte`,de:`Bf.`,en:`box`};function _(e,t=`nl`){let n=e.address;return n?.street?[n.street,n.house_number,n.box?`${g[t]} ${n.box}`:null].filter(Boolean).join(` `):``}function v(e,t=`nl`){let n=e.address,r=[n?.zipcode,n?.municipality].filter(Boolean).join(` `),i=n?.country&&n.country_code!==`BE`?n.country:null,a=[_(e,t),r,i].filter(Boolean).join(`, `);return[e.vat_number,a].filter(Boolean).join(` · `)}function y(e){let t=e.address;return{name:e.name,vat:e.vat_number,enterprise:e.number_formatted,street:t?.street??``,number:t?.house_number??``,box:t?.box??``,zipcode:t?.zipcode??``,city:t?.municipality??``,country:t?.country_code??t?.country??``}}function b(e,t,n){if(!t)return null;if(typeof t!=`string`)return t;try{return e.querySelector(t)}catch{return n(`"${t}" is not a valid selector for a field to fill.`),null}}function x(e,t){if(e instanceof HTMLSelectElement){let n=[...e.options].find(e=>e.value.toLowerCase()===t.toLowerCase());if(!n)return;e.value=n.value}else if(e instanceof HTMLInputElement||e instanceof HTMLTextAreaElement)e.value=t;else return;e.dispatchEvent(new Event(`input`,{bubbles:!0})),e.dispatchEvent(new Event(`change`,{bubbles:!0}))}function S(e,a){let c=e.ownerDocument,g=c.defaultView??window;a.unstyled||m(c);let _=s(a.lang,c),S=o(_),C=Math.min(8,Math.max(1,Math.trunc(a.limit??u))),w=a.endpoint??`https://api.firmatlas.be/v1/autocomplete`,T=`kbo-ac-${++f}`,E=p(),D=h(c,`div`,`kbo-ac-popup`);D.hidden=!0,D.setAttribute(`data-theme`,e.getAttribute(`data-kbo-theme`)===`auto`?`auto`:`light`);let O=h(c,`ul`,`kbo-ac-list`);O.id=`${T}-list`,O.setAttribute(`role`,`listbox`),O.setAttribute(`aria-label`,S.label);let k=h(c,`div`,`kbo-ac-empty`);k.textContent=S.none,k.hidden=!0;let A=h(c,`div`,`kbo-ac-credit`);A.textContent=S.credit,D.append(O,k,A);let j=h(c,`div`,`kbo-ac-status`);j.setAttribute(`role`,`status`),j.setAttribute(`aria-live`,`polite`),c.body.append(D,j);let M={role:e.getAttribute(`role`),autocomplete:e.getAttribute(`autocomplete`)};e.setAttribute(`role`,`combobox`),e.setAttribute(`aria-autocomplete`,`list`),e.setAttribute(`aria-expanded`,`false`),e.setAttribute(`aria-controls`,O.id),e.setAttribute(`autocomplete`,`off`);let N=[],P=-1,F=null,I=null,L=0,R=!1,z=``,B=!1;function V(){let t=e.getBoundingClientRect(),n=D.offsetHeight,r=g.innerHeight-t.bottom,i=r<n+d&&t.top>r;D.dataset.side=i?`top`:`bottom`,D.style.left=`${Math.round(t.left)}px`,D.style.width=`${Math.round(t.width)}px`,D.style.top=i?`${Math.round(t.top-n-d)}px`:`${Math.round(t.bottom+d)}px`}function H(){D.hidden&&(D.hidden=!1,e.setAttribute(`aria-expanded`,`true`),V(),g.addEventListener(`scroll`,V,!0),g.addEventListener(`resize`,V))}function U(){D.hidden||(D.hidden=!0,e.setAttribute(`aria-expanded`,`false`),e.removeAttribute(`aria-activedescendant`),P=-1,g.removeEventListener(`scroll`,V,!0),g.removeEventListener(`resize`,V))}function W(t){let n=O.children;P>=0&&n[P]?.setAttribute(`aria-selected`,`false`),P=t;let r=n[t];r?(r.setAttribute(`aria-selected`,`true`),e.setAttribute(`aria-activedescendant`,r.id),r.scrollIntoView?.({block:`nearest`})):e.removeAttribute(`aria-activedescendant`)}function G(t){N=t,P=-1,e.removeAttribute(`aria-activedescendant`),O.replaceChildren(...t.map((e,t)=>{let n=h(c,`li`,`kbo-ac-option`);n.id=`${T}-opt-${t}`,n.setAttribute(`role`,`option`),n.setAttribute(`aria-selected`,`false`);let r=h(c,`span`,`kbo-ac-name`);r.textContent=e.name;let i=h(c,`span`,`kbo-ac-meta`);return i.textContent=v(e,_),n.append(r,i),n})),k.hidden=t.length>0,j.textContent=t.length?S.results(t.length):S.none,H(),V(),t.length===1&&r(z)&&W(0)}function K(t){let n=N[t];if(!n)return;let r=y(n),i=a.fields??{},o=!1;B=!0;try{for(let t of Object.keys(r)){let n=b(c,i[t],E);n&&(n===e&&(o=!0),x(n,r[t]))}o||x(e,n.name)}finally{B=!1}F&&clearTimeout(F),I?.abort(),L++,z=e.value,U(),e.dispatchEvent(new CustomEvent(`kbo:select`,{bubbles:!0,detail:n})),a.onSelect?.(n)}async function q(r){I?.abort();let i=++L;I=new AbortController;try{let t=await n(r,{key:a.key,lang:_,limit:C,endpoint:w,signal:I.signal});if(i!==L||c.activeElement!==e)return;G(t)}catch(e){if(e instanceof DOMException&&e.name===`AbortError`)return;U(),e instanceof t&&[401,402,403].includes(e.status)?(R=!0,E(`${e.message} Company search is off; the field works as a normal input.`)):E(e instanceof Error?e.message:String(e))}}function J(){if(B)return;let t=e.value;z=t,F&&clearTimeout(F),R||!i(t)?(I?.abort(),L++,U()):F=setTimeout(()=>void q(t.trim()),l)}function Y(t){if(t.isComposing)return;let n=!D.hidden&&N.length>0;switch(t.key){case`ArrowDown`:if(!n){N.length&&e.value===z&&(H(),W(0),t.preventDefault());return}t.preventDefault(),W((P+1)%N.length);return;case`ArrowUp`:if(!n)return;t.preventDefault(),W(P<=0?N.length-1:P-1);return;case`Enter`:n&&P>=0&&(t.preventDefault(),K(P));return;case`Escape`:D.hidden||(t.preventDefault(),U());return;case`Tab`:U();return}}function X(e){e.preventDefault()}function Z(e){let t=e.target?.closest?.(`.kbo-ac-option`);t&&K([...O.children].indexOf(t))}function Q(e){if(e.pointerType!==`mouse`)return;let t=e.target?.closest?.(`.kbo-ac-option`),n=t?[...O.children].indexOf(t):-1;n>=0&&n!==P&&W(n)}function $(){U()}return e.addEventListener(`input`,J),e.addEventListener(`keydown`,Y),e.addEventListener(`blur`,$),D.addEventListener(`pointerdown`,X),D.addEventListener(`mousedown`,X),O.addEventListener(`click`,Z),O.addEventListener(`pointermove`,Q),{destroy(){F&&clearTimeout(F),I?.abort(),U(),e.removeEventListener(`input`,J),e.removeEventListener(`keydown`,Y),e.removeEventListener(`blur`,$),D.remove(),j.remove();for(let t of[`aria-autocomplete`,`aria-expanded`,`aria-controls`,`aria-activedescendant`])e.removeAttribute(t);M.role===null?e.removeAttribute(`role`):e.setAttribute(`role`,M.role),M.autocomplete===null?e.removeAttribute(`autocomplete`):e.setAttribute(`autocomplete`,M.autocomplete)}}}var C=[`name`,`vat`,`enterprise`,`street`,`number`,`box`,`zipcode`,`city`,`country`],w=new WeakMap;function T(e=document){let t=[];for(let n of e.querySelectorAll(`input[data-kbo-autocomplete]`)){if(w.has(n))continue;let e=n.dataset;if(!e.kboKey){console.warn(`[firmatlas-autocomplete] data-kbo-key is missing; the field works as a normal input.`,n);continue}let r={};for(let t of C){let n=e[`kboFill${t[0].toUpperCase()}${t.slice(1)}`];n&&(r[t]=n)}let i=S(n,{key:e.kboKey,fields:r,...e.kboLang?{lang:e.kboLang}:{},...e.kboLimit?{limit:Number(e.kboLimit)}:{},...e.kboEndpoint?{endpoint:e.kboEndpoint}:{},unstyled:e.kboUnstyled!==void 0});w.set(n,i),t.push(i)}return t}return document.readyState===`loading`?document.addEventListener(`DOMContentLoaded`,()=>T(),{once:!0}):T(),e.attach=S,e.init=T,e})({});