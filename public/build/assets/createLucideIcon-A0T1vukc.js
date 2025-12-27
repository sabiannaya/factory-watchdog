import{u as f}from"./AppLogoIcon.vue_vue_type_script_setup_true_lang-BzC2dvNG.js";import{g as m,f as p,a0 as b,a1 as a}from"./app-D4NX6RtZ.js";function k(){const e=b(),r=m(),s=p(()=>["#text","#comment"].includes(r.value?.$el.nodeName)?r.value?.$el.nextElementSibling:f(r)),o=Object.assign({},e.exposed),n={};for(const t in e.props)Object.defineProperty(n,t,{enumerable:!0,configurable:!0,get:()=>e.props[t]});if(Object.keys(o).length>0)for(const t in o)Object.defineProperty(n,t,{enumerable:!0,configurable:!0,get:()=>o[t]});Object.defineProperty(n,"$el",{enumerable:!0,configurable:!0,get:()=>e.vnode.el}),e.exposed=n;function c(t){r.value=t,t&&(Object.defineProperty(n,"$el",{enumerable:!0,configurable:!0,get:()=>t instanceof Element?t:t.$el}),e.exposed=n)}return{forwardRef:c,currentRef:r,currentElement:s}}/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const g=e=>e.replace(/([a-z0-9])([A-Z])/g,"$1-$2").toLowerCase();/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */var u={xmlns:"http://www.w3.org/2000/svg",width:24,height:24,viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":2,"stroke-linecap":"round","stroke-linejoin":"round"};/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const h=({size:e,strokeWidth:r=2,absoluteStrokeWidth:s,color:o,iconNode:n,name:c,class:t,...i},{slots:l})=>a("svg",{...u,width:e||u.width,height:e||u.height,stroke:o||u.stroke,"stroke-width":s?Number(r)*24/Number(e):r,class:["lucide",`lucide-${g(c??"icon")}`],...i},[...n.map(d=>a(...d)),...l.default?[l.default()]:[]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const v=(e,r)=>(s,{slots:o})=>a(h,{...s,iconNode:r,name:e},o);export{v as c,k as u};
