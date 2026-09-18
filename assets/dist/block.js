(function(blocks,element,components,blockEditor,serverSideRender,i18n){
  'use strict';
  const el=element.createElement;
  blocks.registerBlockType('orchestrix-forms/form',{
    apiVersion:3,title:i18n.__('Orchestrix Form','orchestrix-forms'),icon:'feedback',category:'widgets',
    attributes:{formId:{type:'integer',default:0}},
    edit:function(props){return el(element.Fragment,null,
      el(blockEditor.InspectorControls,null,el(components.PanelBody,{title:i18n.__('Form settings','orchestrix-forms')},
        el(components.TextControl,{label:i18n.__('Form ID','orchestrix-forms'),type:'number',min:1,value:props.attributes.formId||'',onChange:function(value){props.setAttributes({formId:parseInt(value,10)||0});}}))),
      props.attributes.formId?el(serverSideRender,{block:'orchestrix-forms/form',attributes:props.attributes}):el(components.Placeholder,{icon:'feedback',label:i18n.__('Orchestrix Form','orchestrix-forms')},i18n.__('Enter a published form ID in block settings.','orchestrix-forms'))
    );},save:function(){return null;}
  });
})(window.wp.blocks,window.wp.element,window.wp.components,window.wp.blockEditor,window.wp.serverSideRender,window.wp.i18n);
