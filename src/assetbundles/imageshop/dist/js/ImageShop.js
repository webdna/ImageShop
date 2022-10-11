/**
 * ImageShop plugin for Craft CMS
 *
 * ImageShopField Field JS
 *
 * @author    WebDNA
 * @copyright Copyright (c) 2022 WebDNA
 * @link      https://webdna.co.uk
 * @package   ImageShop
 * @since     2.0.0ImageShop
 */

 (function ($) {
 
     Craft.ImageShopDAMField = Garnish.Base.extend(
         {
             $container: null,
             $trigger: null,
             $hiddenInput: null,
             $previewInput: null,
             $removeButton: null,
             $url: null,
             $popupWindow: null,
             $open: false,
 
             init: function (options) {
                 
                 this.$container = $('[data-id="' + options.namespace + 'imageshop"]');
                 this.$url = options.url;
                 this.$trigger = this.$container.find(".imageshop-trigger");
                 this.$hiddenInput = this.$container.find(".imageshop-value");
                 this.$previewInput = this.$container.find(".imageshop-preview");
                 this.$removeButton = this.$container.find(".imageshop-remove");
 
                 this.addListener(this.$trigger, "click", "showPopup");
                 this.addListener(this.$removeButton, "click", "removeSelection");
 
 
                 window.addEventListener("message", function (event) {
                     if (event.origin == 'https://client.imageshop.no') {
                         if (this.$open) {
                             this.$open = false;
                             this.$hiddenInput.val(event.data);
                             this.$popupWindow.close();
                             this.updatePreview(event.data);
                         }
                     }
                 }.bind(this), false);
             },
 
             removeSelection: function () {
                 this.$hiddenInput.val(null);
                 $("img", this.$previewInput).remove();
                 $(".imageshop-label", this.$previewInput).remove();
             },
 
             removePreview: function () {
                 $("img", this.$previewInput).remove();
                 $(".imageshop-label", this.$previewInput).remove();
             },
 
             showPopup: function (ev) {
                 ev.preventDefault();
                 this.$open = true;
 
                 // Sensible defaults
                 var width = 950;
                 var height = 650;
 
                 var leftPosition = (screen.width) ? (screen.width - width) / 2 : 0;
                 var topPosition = (screen.height) ? (screen.height - height) / 2 : 0;
                 var settings = 'height=' + height + ',width=' + width + ',top=' + topPosition + ',left=' + leftPosition + ',resizable';
                 this.$popupWindow = window.open(this.$url, "imageshop", settings);
             },
 
             updatePreview: function (data) {
                 var json = JSON.parse(data);
                 var url = json.image.file;
                 var label = json.text.no.title || json.code;
 
                 this.removePreview();
 
                 this.$previewInput.prepend($('<img>', {
                     width: 100,
                     src: url
                 }));
 
                 var labelDiv = $("<div class='imageshop-label'>");
                 var inner1 = $("<div class='label'><span class='title'>" + label + "</span></div>");
                 var inner2 = $("<a class='delete icon imageshop-remove' title='Remove'></a>");
                 this.addListener(inner2, "click", "removeSelection");
 
                 labelDiv.append(inner1);
                 labelDiv.append(inner2);
                 this.$previewInput.append(labelDiv);
             }
         });
 
 })(jQuery);
