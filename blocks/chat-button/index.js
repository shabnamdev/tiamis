(function (blocks, element, blockEditor, components) {
  'use strict';
  if (!blocks || !element || !blockEditor) return;
  var el = element.createElement;
  blocks.registerBlockType('shcd-tiamis/chat-button', {
    edit: function (props) {
      var attributes = props.attributes || {};
      var setAttributes = props.setAttributes;
      var useBlockProps = blockEditor.useBlockProps;
      var RichText = blockEditor.RichText;
      var blockProps = useBlockProps({ className: 'wp-block-shcd-tiamis-chat-button-wrap' });
      return el('div', blockProps,
        el(RichText, {
          tagName: 'button',
          className: 'wp-block-shcd-tiamis-chat-button',
          value: attributes.label,
          allowedFormats: [],
          placeholder: 'متن دکمه…',
          onChange: function (label) { setAttributes({ label: label }); }
        })
      );
    },
    save: function () { return null; }
  });
})(window.wp && window.wp.blocks, window.wp && window.wp.element, window.wp && window.wp.blockEditor, window.wp && window.wp.components);
