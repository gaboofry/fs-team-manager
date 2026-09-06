/*! Fabriel Software Team-Manager v1.2.2
 *
 * This file is GENERATED - do not edit it directly.
 * Source file:  src/blocks/tables-overview/index.js
 * Rebuild with: npm ci && npm run build
 * Source code:  https://github.com/gaboofry/fs-team-manager
 *
 * @package   fs-team-manager
 * @author    Fabriel Software (https://fabrielsoftware.de/)
 * @copyright Fabriel Software
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://teammanager.fabrielsoftware.de/
 */
(()=>{"use strict";const e=window.wp.blocks,t=JSON.parse('{"UU":"fstm/fussball-tables-overview"}'),a=window.React,n=window.wp.i18n,l=window.wp.blockEditor,r=window.wp.components;(0,e.registerBlockType)(t.UU,{edit:function({attributes:e,setAttributes:t}){const{columns:s="2"}=e,o=window.fsTmDesign||{},i=o.previewStyle?o.previewStyle(e):{},m=o.SchemePanel,c=(0,l.useBlockProps)({style:{...i,borderRadius:"12px",overflow:"hidden",border:"1px solid rgba(0,0,0,0.12)",boxShadow:"0 4px 14px rgba(0,0,0,0.08)",cursor:"pointer"}});return(0,a.createElement)(a.Fragment,null,(0,a.createElement)(l.InspectorControls,null,(0,a.createElement)(r.PanelBody,{title:(0,n.__)("Layout Einstellungen","fs-team-manager"),initialOpen:!0},(0,a.createElement)(r.SelectControl,{label:(0,n.__)("Spalten:","fs-team-manager"),help:(0,n.__)("Anzahl der Spalten auf Desktop-Bildschirmen.","fs-team-manager"),value:s,options:[{label:"1 "+(0,n.__)("Spalte","fs-team-manager"),value:"1"},{label:"2 "+(0,n.__)("Spalten","fs-team-manager"),value:"2"},{label:"3 "+(0,n.__)("Spalten","fs-team-manager"),value:"3"}],onChange:e=>t({columns:e})})),m&&(0,a.createElement)(m,{attributes:e,setAttributes:t})),(0,a.createElement)("div",{...c},(0,a.createElement)("div",{style:{padding:"12px 18px",background:"var(--fs-tm-card-head-bg)",color:"var(--fs-tm-card-ink)",textAlign:"center",fontWeight:700,fontSize:"14px"}},(0,a.createElement)("span",{style:{marginRight:"6px"}},"🏆"),"Tabellen-Übersicht (Grid)"),(0,a.createElement)("div",{style:{padding:"18px 20px",background:"var(--fs-tm-card-body-bg)",color:"var(--fs-tm-body-ink)",textAlign:"center",fontSize:"13px"}},(0,a.createElement)("strong",{style:{display:"block",marginBottom:"4px"}},(0,n.__)("Raster-Ansicht","fs-team-manager")," (",s," ",(0,n.__)("Spalten","fs-team-manager"),")"),(0,a.createElement)("span",{style:{color:"var(--fs-tm-body-ink-soft)",fontSize:"12px"}},(0,n.__)("Zeigt alle aktiven Tabellen automatisch gruppiert an.","fs-team-manager")))))},save:()=>null})})();