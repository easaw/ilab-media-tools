//
// jQuery MiniColors: A tiny color picker built on jQuery
//
// Developed by Cory LaViska for A Beautiful Site, LLC
//
// Licensed under the MIT license: http://opensource.org/licenses/MIT
//
(function (factory) {
  if(typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define(['jquery'], factory);
  } else if(typeof exports === 'object') {
    // Node/CommonJS
    module.exports = factory(require('jquery'));
  } else {
    // Browser globals
    factory(jQuery);
  }
}(function ($) {
  'use strict';

  // Defaults
  $.minicolors = {
    defaults: {
      animationSpeed: 50,
      animationEasing: 'swing',
      change: null,
      changeDelay: 0,
      control: 'hue',
      defaultValue: '',
      format: 'hex',
      hide: null,
      hideSpeed: 100,
      inline: false,
      keywords: '',
      letterCase: 'lowercase',
      opacity: false,
      position: 'bottom',
      show: null,
      showSpeed: 100,
      theme: 'default',
      swatches: []
    }
  };

  // Public methods
  $.extend($.fn, {
    minicolors: function(method, data) {

      switch(method) {
      // Destroy the control
      case 'destroy':
        $(this).each(function() {
          destroy($(this));
        });
        return $(this);

      // Hide the color picker
      case 'hide':
        hide();
        return $(this);

      // Get/set opacity
      case 'opacity':
        // Getter
        if(data === undefined) {
          // Getter
          return $(this).attr('data-opacity');
        } else {
          // Setter
          $(this).each(function() {
            updateFromInput($(this).attr('data-opacity', data));
          });
        }
        return $(this);

      // Get an RGB(A) object based on the current color/opacity
      case 'rgbObject':
        return rgbObject($(this), method === 'rgbaObject');

      // Get an RGB(A) string based on the current color/opacity
      case 'rgbString':
      case 'rgbaString':
        return rgbString($(this), method === 'rgbaString');

      // Get/set settings on the fly
      case 'settings':
        if(data === undefined) {
          return $(this).data('minicolors-settings');
        } else {
          // Setter
          $(this).each(function() {
            var settings = $(this).data('minicolors-settings') || {};
            destroy($(this));
            $(this).minicolors($.extend(true, settings, data));
          });
        }
        return $(this);

      // Show the color picker
      case 'show':
        show($(this).eq(0));
        return $(this);

      // Get/set the hex color value
      case 'value':
        if(data === undefined) {
          // Getter
          return $(this).val();
        } else {
          // Setter
          $(this).each(function() {
            if(typeof(data) === 'object' && data !== null) {
              if(data.opacity !== undefined) {
                $(this).attr('data-opacity', keepWithin(data.opacity, 0, 1));
              }
              if(data.color) {
                $(this).val(data.color);
              }
            } else {
              $(this).val(data);
            }
            updateFromInput($(this));
          });
        }
        return $(this);

      // Initializes the control
      default:
        if(method !== 'create') data = method;
        $(this).each(function() {
          init($(this), data);
        });
        return $(this);

      }

    }
  });

  // Initialize input elements
  function init(input, settings) {
    var minicolors = $('<div class="minicolors" />');
    var defaults = $.minicolors.defaults;
    var name;
    var size;
    var swatches;
    var swatch;
    var panel;
    var i;

    // Do nothing if already initialized
    if(input.data('minicolors-initialized')) return;

    // Handle settings
    settings = $.extend(true, {}, defaults, settings);

    // The wrapper
    minicolors
      .addClass('minicolors-theme-' + settings.theme)
      .toggleClass('minicolors-with-opacity', settings.opacity);

    // Custom positioning
    if(settings.position !== undefined) {
      $.each(settings.position.split(' '), function() {
        minicolors.addClass('minicolors-position-' + this);
      });
    }

    // Input size
    if(settings.format === 'rgb') {
      size = settings.opacity ? '25' : '20';
    } else {
      size = settings.keywords ? '11' : '7';
    }

    // The input
    input
      .addClass('minicolors-input')
      .data('minicolors-initialized', false)
      .data('minicolors-settings', settings)
      .prop('size', size)
      .wrap(minicolors)
      .after(
        '<div class="minicolors-panel minicolors-slider-' + settings.control + '">' +
      '<div class="minicolors-slider minicolors-sprite">' +
      '<div class="minicolors-picker"></div>' +
      '</div>' +
      '<div class="minicolors-opacity-slider minicolors-sprite">' +
      '<div class="minicolors-picker"></div>' +
      '</div>' +
      '<div class="minicolors-grid minicolors-sprite">' +
      '<div class="minicolors-grid-inner"></div>' +
      '<div class="minicolors-picker"><div></div></div>' +
      '</div>' +
      '</div>'
      );

    // The swatch
    if(!settings.inline) {
      input.after('<span class="minicolors-swatch minicolors-sprite minicolors-input-swatch"><span class="minicolors-swatch-color"></span></span>');
      input.next('.minicolors-input-swatch').on('click', function(event) {
        event.preventDefault();
        input.focus();
      });
    }

    // Prevent text selection in IE
    panel = input.parent().find('.minicolors-panel');
    panel.on('selectstart', function() { return false; }).end();

    // Swatches
    if(settings.swatches && settings.swatches.length !== 0) {
      panel.addClass('minicolors-with-swatches');
      swatches = $('<ul class="minicolors-swatches"></ul>')
        .appendTo(panel);
      for(i = 0; i < settings.swatches.length; ++i) {
        // allow for custom objects as swatches
        if($.type(settings.swatches[i]) === 'object') {
          name = settings.swatches[i].name;
          swatch = settings.swatches[i].color;
        } else {
          name = '';
          swatch = settings.swatches[i];
        }
        swatch = isRgb(swatch) ? parseRgb(swatch, true) : hex2rgb(parseHex(swatch, true));
        $('<li class="minicolors-swatch minicolors-sprite"><span class="minicolors-swatch-color" title="' + name + '"></span></li>')
          .appendTo(swatches)
          .data('swatch-color', settings.swatches[i])
          .find('.minicolors-swatch-color')
          .css({
            backgroundColor: rgb2hex(swatch),
            opacity: swatch.a
          });
        settings.swatches[i] = swatch;
      }
    }

    // Inline controls
    if(settings.inline) input.parent().addClass('minicolors-inline');

    updateFromInput(input, false);

    input.data('minicolors-initialized', true);
  }

  // Returns the input back to its original state
  function destroy(input) {
    var minicolors = input.parent();

    // Revert the input element
    input
      .removeData('minicolors-initialized')
      .removeData('minicolors-settings')
      .removeProp('size')
      .removeClass('minicolors-input');

    // Remove the wrap and destroy whatever remains
    minicolors.before(input).remove();
  }

  // Shows the specified dropdown panel
  function show(input) {
    var minicolors = input.parent();
    var panel = minicolors.find('.minicolors-panel');
    var settings = input.data('minicolors-settings');

    // Do nothing if uninitialized, disabled, inline, or already open
    if(
      !input.data('minicolors-initialized') ||
      input.prop('disabled') ||
      minicolors.hasClass('minicolors-inline') ||
      minicolors.hasClass('minicolors-focus')
    ) return;

    hide();

    minicolors.addClass('minicolors-focus');
    if (panel.animate) {
      panel
        .stop(true, true)
        .fadeIn(settings.showSpeed, function () {
          if (settings.show) settings.show.call(input.get(0));
        });
    } else {
      panel.css('opacity', 1);
      if (settings.show) settings.show.call(input.get(0));
    }
  }

  // Hides all dropdown panels
  function hide() {
    $('.minicolors-focus').each(function() {
      var minicolors = $(this);
      var input = minicolors.find('.minicolors-input');
      var panel = minicolors.find('.minicolors-panel');
      var settings = input.data('minicolors-settings');

      if (panel.animate) {
        panel.fadeOut(settings.hideSpeed, function () {
          if (settings.hide) settings.hide.call(input.get(0));
          minicolors.removeClass('minicolors-focus');
        });
      } else {
        panel.css('opacity', 0);
        if (settings.hide) settings.hide.call(input.get(0));
        minicolors.removeClass('minicolors-focus');
      }
    });
  }

  // Moves the selected picker
  function move(target, event, animate) {
    var input = target.parents('.minicolors').find('.minicolors-input');
    var settings = input.data('minicolors-settings');
    var picker = target.find('[class$=-picker]');
    var offsetX = target.offset().left;
    var offsetY = target.offset().top;
    var x = Math.round(event.pageX - offsetX);
    var y = Math.round(event.pageY - offsetY);
    var duration = animate ? settings.animationSpeed : 0;
    var wx, wy, r, phi, styles;

    // Touch support
    if(event.originalEvent.changedTouches) {
      x = event.originalEvent.changedTouches[0].pageX - offsetX;
      y = event.originalEvent.changedTouches[0].pageY - offsetY;
    }

    // Constrain picker to its container
    if(x < 0) x = 0;
    if(y < 0) y = 0;
    if(x > target.width()) x = target.width();
    if(y > target.height()) y = target.height();

    // Constrain color wheel values to the wheel
    if(target.parent().is('.minicolors-slider-wheel') && picker.parent().is('.minicolors-grid')) {
      wx = 75 - x;
      wy = 75 - y;
      r = Math.sqrt(wx * wx + wy * wy);
      phi = Math.atan2(wy, wx);
      if(phi < 0) phi += Math.PI * 2;
      if(r > 75) {
        r = 75;
        x = 75 - (75 * Math.cos(phi));
        y = 75 - (75 * Math.sin(phi));
      }
      x = Math.round(x);
      y = Math.round(y);
    }

    // Move the picker
    styles = {
      top: y + 'px'
    };
    if(target.is('.minicolors-grid')) {
      styles.left = x + 'px';
    }
    if (picker.animate) {
      picker
        .stop(true)
        .animate(styles, duration, settings.animationEasing, function() {
          updateFromControl(input, target);
        });
    } else {
      picker
        .css(styles);
      updateFromControl(input, target);
    }
  }

  // Sets the input based on the color picker values
  function updateFromControl(input, target) {

    function getCoords(picker, container) {
      var left, top;
      if(!picker.length || !container) return null;
      left = picker.offset().left;
      top = picker.offset().top;

      return {
        x: left - container.offset().left + (picker.outerWidth() / 2),
        y: top - container.offset().top + (picker.outerHeight() / 2)
      };
    }

    var hue, saturation, brightness, x, y, r, phi;
    var hex = input.val();
    var opacity = input.attr('data-opacity');

    // Helpful references
    var minicolors = input.parent();
    var settings = input.data('minicolors-settings');
    var swatch = minicolors.find('.minicolors-input-swatch');

    // Panel objects
    var grid = minicolors.find('.minicolors-grid');
    var slider = minicolors.find('.minicolors-slider');
    var opacitySlider = minicolors.find('.minicolors-opacity-slider');

    // Picker objects
    var gridPicker = grid.find('[class$=-picker]');
    var sliderPicker = slider.find('[class$=-picker]');
    var opacityPicker = opacitySlider.find('[class$=-picker]');

    // Picker positions
    var gridPos = getCoords(gridPicker, grid);
    var sliderPos = getCoords(sliderPicker, slider);
    var opacityPos = getCoords(opacityPicker, opacitySlider);

    // Handle colors
    if(target.is('.minicolors-grid, .minicolors-slider, .minicolors-opacity-slider')) {

      // Determine HSB values
      switch(settings.control) {
      case 'wheel':
        // Calculate hue, saturation, and brightness
        x = (grid.width() / 2) - gridPos.x;
        y = (grid.height() / 2) - gridPos.y;
        r = Math.sqrt(x * x + y * y);
        phi = Math.atan2(y, x);
        if(phi < 0) phi += Math.PI * 2;
        if(r > 75) {
          r = 75;
          gridPos.x = 69 - (75 * Math.cos(phi));
          gridPos.y = 69 - (75 * Math.sin(phi));
        }
        saturation = keepWithin(r / 0.75, 0, 100);
        hue = keepWithin(phi * 180 / Math.PI, 0, 360);
        brightness = keepWithin(100 - Math.floor(sliderPos.y * (100 / slider.height())), 0, 100);
        hex = hsb2hex({
          h: hue,
          s: saturation,
          b: brightness
        });

        // Update UI
        slider.css('backgroundColor', hsb2hex({ h: hue, s: saturation, b: 100 }));
        break;

      case 'saturation':
        // Calculate hue, saturation, and brightness
        hue = keepWithin(parseInt(gridPos.x * (360 / grid.width()), 10), 0, 360);
        saturation = keepWithin(100 - Math.floor(sliderPos.y * (100 / slider.height())), 0, 100);
        brightness = keepWithin(100 - Math.floor(gridPos.y * (100 / grid.height())), 0, 100);
        hex = hsb2hex({
          h: hue,
          s: saturation,
          b: brightness
        });

        // Update UI
        slider.css('backgroundColor', hsb2hex({ h: hue, s: 100, b: brightness }));
        minicolors.find('.minicolors-grid-inner').css('opacity', saturation / 100);
        break;

      case 'brightness':
        // Calculate hue, saturation, and brightness
        hue = keepWithin(parseInt(gridPos.x * (360 / grid.width()), 10), 0, 360);
        saturation = keepWithin(100 - Math.floor(gridPos.y * (100 / grid.height())), 0, 100);
        brightness = keepWithin(100 - Math.floor(sliderPos.y * (100 / slider.height())), 0, 100);
        hex = hsb2hex({
          h: hue,
          s: saturation,
          b: brightness
        });

        // Update UI
        slider.css('backgroundColor', hsb2hex({ h: hue, s: saturation, b: 100 }));
        minicolors.find('.minicolors-grid-inner').css('opacity', 1 - (brightness / 100));
        break;

      default:
        // Calculate hue, saturation, and brightness
        hue = keepWithin(360 - parseInt(sliderPos.y * (360 / slider.height()), 10), 0, 360);
        saturation = keepWithin(Math.floor(gridPos.x * (100 / grid.width())), 0, 100);
        brightness = keepWithin(100 - Math.floor(gridPos.y * (100 / grid.height())), 0, 100);
        hex = hsb2hex({
          h: hue,
          s: saturation,
          b: brightness
        });

        // Update UI
        grid.css('backgroundColor', hsb2hex({ h: hue, s: 100, b: 100 }));
        break;
      }

      // Handle opacity
      if(settings.opacity) {
        opacity = parseFloat(1 - (opacityPos.y / opacitySlider.height())).toFixed(2);
      } else {
        opacity = 1;
      }

      updateInput(input, hex, opacity);
    }
    else {
      // Set swatch color
      swatch.find('span').css({
        backgroundColor: hex,
        opacity: opacity
      });

      // Handle change event
      doChange(input, hex, opacity);
    }
  }

  // Sets the value of the input and does the appropriate conversions
  // to respect settings, also updates the swatch
  function updateInput(input, value, opacity) {
    var rgb;

    // Helpful references
    var minicolors = input.parent();
    var settings = input.data('minicolors-settings');
    var swatch = minicolors.find('.minicolors-input-swatch');

    if(settings.opacity) input.attr('data-opacity', opacity);

    // Set color string
    if(settings.format === 'rgb') {
      // Returns RGB(A) string

      // Checks for input format and does the conversion
      if(isRgb(value)) {
        rgb = parseRgb(value, true);
      }
      else {
        rgb = hex2rgb(parseHex(value, true));
      }

      opacity = input.attr('data-opacity') === '' ? 1 : keepWithin(parseFloat(input.attr('data-opacity')).toFixed(2), 0, 1);
      if(isNaN(opacity) || !settings.opacity) opacity = 1;

      if(input.minicolors('rgbObject').a <= 1 && rgb && settings.opacity) {
        // Set RGBA string if alpha
        value = 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + parseFloat(opacity) + ')';
      } else {
        // Set RGB string (alpha = 1)
        value = 'rgb(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ')';
      }
    } else {
      // Returns hex color

      // Checks for input format and does the conversion
      if(isRgb(value)) {
        value = rgbString2hex(value);
      }

      value = convertCase(value, settings.letterCase);
    }

    // Update value from picker
    input.val(value);

    // Set swatch color
    swatch.find('span').css({
      backgroundColor: value,
      opacity: opacity
    });

    // Handle change event
    doChange(input, value, opacity);
  }

  // Sets the color picker values from the input
  function updateFromInput(input, preserveInputValue) {
    var hex, hsb, opacity, keywords, alpha, value, x, y, r, phi;

    // Helpful references
    var minicolors = input.parent();
    var settings = input.data('minicolors-settings');
    var swatch = minicolors.find('.minicolors-input-swatch');

    // Panel objects
    var grid = minicolors.find('.minicolors-grid');
    var slider = minicolors.find('.minicolors-slider');
    var opacitySlider = minicolors.find('.minicolors-opacity-slider');

    // Picker objects
    var gridPicker = grid.find('[class$=-picker]');
    var sliderPicker = slider.find('[class$=-picker]');
    var opacityPicker = opacitySlider.find('[class$=-picker]');

    // Determine hex/HSB values
    if(isRgb(input.val())) {
      // If input value is a rgb(a) string, convert it to hex color and update opacity
      hex = rgbString2hex(input.val());
      alpha = keepWithin(parseFloat(getAlpha(input.val())).toFixed(2), 0, 1);
      if(alpha) {
        input.attr('data-opacity', alpha);
      }
    } else {
      hex = convertCase(parseHex(input.val(), true), settings.letterCase);
    }

    if(!hex){
      hex = convertCase(parseInput(settings.defaultValue, true), settings.letterCase);
    }
    hsb = hex2hsb(hex);

    // Get array of lowercase keywords
    keywords = !settings.keywords ? [] : $.map(settings.keywords.split(','), function(a) {
      return $.trim(a.toLowerCase());
    });

    // Set color string
    if(input.val() !== '' && $.inArray(input.val().toLowerCase(), keywords) > -1) {
      value = convertCase(input.val());
    } else {
      value = isRgb(input.val()) ? parseRgb(input.val()) : hex;
    }

    // Update input value
    if(!preserveInputValue) input.val(value);

    // Determine opacity value
    if(settings.opacity) {
      // Get from data-opacity attribute and keep within 0-1 range
      opacity = input.attr('data-opacity') === '' ? 1 : keepWithin(parseFloat(input.attr('data-opacity')).toFixed(2), 0, 1);
      if(isNaN(opacity)) opacity = 1;
      input.attr('data-opacity', opacity);
      swatch.find('span').css('opacity', opacity);

      // Set opacity picker position
      y = keepWithin(opacitySlider.height() - (opacitySlider.height() * opacity), 0, opacitySlider.height());
      opacityPicker.css('top', y + 'px');
    }

    // Set opacity to zero if input value is transparent
    if(input.val().toLowerCase() === 'transparent') {
      swatch.find('span').css('opacity', 0);
    }

    // Update swatch
    swatch.find('span').css('backgroundColor', hex);

    // Determine picker locations
    switch(settings.control) {
    case 'wheel':
      // Set grid position
      r = keepWithin(Math.ceil(hsb.s * 0.75), 0, grid.height() / 2);
      phi = hsb.h * Math.PI / 180;
      x = keepWithin(75 - Math.cos(phi) * r, 0, grid.width());
      y = keepWithin(75 - Math.sin(phi) * r, 0, grid.height());
      gridPicker.css({
        top: y + 'px',
        left: x + 'px'
      });

      // Set slider position
      y = 150 - (hsb.b / (100 / grid.height()));
      if(hex === '') y = 0;
      sliderPicker.css('top', y + 'px');

      // Update panel color
      slider.css('backgroundColor', hsb2hex({ h: hsb.h, s: hsb.s, b: 100 }));
      break;

    case 'saturation':
      // Set grid position
      x = keepWithin((5 * hsb.h) / 12, 0, 150);
      y = keepWithin(grid.height() - Math.ceil(hsb.b / (100 / grid.height())), 0, grid.height());
      gridPicker.css({
        top: y + 'px',
        left: x + 'px'
      });

      // Set slider position
      y = keepWithin(slider.height() - (hsb.s * (slider.height() / 100)), 0, slider.height());
      sliderPicker.css('top', y + 'px');

      // Update UI
      slider.css('backgroundColor', hsb2hex({ h: hsb.h, s: 100, b: hsb.b }));
      minicolors.find('.minicolors-grid-inner').css('opacity', hsb.s / 100);
      break;

    case 'brightness':
      // Set grid position
      x = keepWithin((5 * hsb.h) / 12, 0, 150);
      y = keepWithin(grid.height() - Math.ceil(hsb.s / (100 / grid.height())), 0, grid.height());
      gridPicker.css({
        top: y + 'px',
        left: x + 'px'
      });

      // Set slider position
      y = keepWithin(slider.height() - (hsb.b * (slider.height() / 100)), 0, slider.height());
      sliderPicker.css('top', y + 'px');

      // Update UI
      slider.css('backgroundColor', hsb2hex({ h: hsb.h, s: hsb.s, b: 100 }));
      minicolors.find('.minicolors-grid-inner').css('opacity', 1 - (hsb.b / 100));
      break;

    default:
      // Set grid position
      x = keepWithin(Math.ceil(hsb.s / (100 / grid.width())), 0, grid.width());
      y = keepWithin(grid.height() - Math.ceil(hsb.b / (100 / grid.height())), 0, grid.height());
      gridPicker.css({
        top: y + 'px',
        left: x + 'px'
      });

      // Set slider position
      y = keepWithin(slider.height() - (hsb.h / (360 / slider.height())), 0, slider.height());
      sliderPicker.css('top', y + 'px');

      // Update panel color
      grid.css('backgroundColor', hsb2hex({ h: hsb.h, s: 100, b: 100 }));
      break;
    }

    // Fire change event, but only if minicolors is fully initialized
    if(input.data('minicolors-initialized')) {
      doChange(input, value, opacity);
    }
  }

  // Runs the change and changeDelay callbacks
  function doChange(input, value, opacity) {
    var settings = input.data('minicolors-settings');
    var lastChange = input.data('minicolors-lastChange');
    var obj, sel, i;

    // Only run if it actually changed
    if(!lastChange || lastChange.value !== value || lastChange.opacity !== opacity) {

      // Remember last-changed value
      input.data('minicolors-lastChange', {
        value: value,
        opacity: opacity
      });

      // Check and select applicable swatch
      if(settings.swatches && settings.swatches.length !== 0) {
        if(!isRgb(value)) {
          obj = hex2rgb(value);
        }
        else {
          obj = parseRgb(value, true);
        }
        sel = -1;
        for(i = 0; i < settings.swatches.length; ++i) {
          if(obj.r === settings.swatches[i].r && obj.g === settings.swatches[i].g && obj.b === settings.swatches[i].b && obj.a === settings.swatches[i].a) {
            sel = i;
            break;
          }
        }

        input.parent().find('.minicolors-swatches .minicolors-swatch').removeClass('selected');
        if(sel !== -1) {
          input.parent().find('.minicolors-swatches .minicolors-swatch').eq(i).addClass('selected');
        }
      }

      // Fire change event
      if(settings.change) {
        if(settings.changeDelay) {
          // Call after a delay
          clearTimeout(input.data('minicolors-changeTimeout'));
          input.data('minicolors-changeTimeout', setTimeout(function() {
            settings.change.call(input.get(0), value, opacity);
          }, settings.changeDelay));
        } else {
          // Call immediately
          settings.change.call(input.get(0), value, opacity);
        }
      }
      input.trigger('change').trigger('input');
    }
  }

  // Generates an RGB(A) object based on the input's value
  function rgbObject(input) {
    var rgb,
      opacity = $(input).attr('data-opacity');
    if( isRgb($(input).val()) ) {
      rgb = parseRgb($(input).val(), true);
    } else {
      var hex = parseHex($(input).val(), true);
      rgb = hex2rgb(hex);
    }
    if( !rgb ) return null;
    if( opacity !== undefined ) $.extend(rgb, { a: parseFloat(opacity) });
    return rgb;
  }

  // Generates an RGB(A) string based on the input's value
  function rgbString(input, alpha) {
    var rgb,
      opacity = $(input).attr('data-opacity');
    if( isRgb($(input).val()) ) {
      rgb = parseRgb($(input).val(), true);
    } else {
      var hex = parseHex($(input).val(), true);
      rgb = hex2rgb(hex);
    }
    if( !rgb ) return null;
    if( opacity === undefined ) opacity = 1;
    if( alpha ) {
      return 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + parseFloat(opacity) + ')';
    } else {
      return 'rgb(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ')';
    }
  }

  // Converts to the letter case specified in settings
  function convertCase(string, letterCase) {
    return letterCase === 'uppercase' ? string.toUpperCase() : string.toLowerCase();
  }

  // Parses a string and returns a valid hex string when possible
  function parseHex(string, expand) {
    string = string.replace(/^#/g, '');
    if(!string.match(/^[A-F0-9]{3,6}/ig)) return '';
    if(string.length !== 3 && string.length !== 6) return '';
    if(string.length === 3 && expand) {
      string = string[0] + string[0] + string[1] + string[1] + string[2] + string[2];
    }
    return '#' + string;
  }

  // Parses a string and returns a valid RGB(A) string when possible
  function parseRgb(string, obj) {
    var values = string.replace(/[^\d,.]/g, '');
    var rgba = values.split(',');

    rgba[0] = keepWithin(parseInt(rgba[0], 10), 0, 255);
    rgba[1] = keepWithin(parseInt(rgba[1], 10), 0, 255);
    rgba[2] = keepWithin(parseInt(rgba[2], 10), 0, 255);
    if(rgba[3] !== undefined) {
      rgba[3] = keepWithin(parseFloat(rgba[3], 10), 0, 1);
    }

    // Return RGBA object
    if( obj ) {
      if (rgba[3] !== undefined) {
        return {
          r: rgba[0],
          g: rgba[1],
          b: rgba[2],
          a: rgba[3]
        };
      } else {
        return {
          r: rgba[0],
          g: rgba[1],
          b: rgba[2]
        };
      }
    }

    // Return RGBA string
    if(typeof(rgba[3]) !== 'undefined' && rgba[3] <= 1) {
      return 'rgba(' + rgba[0] + ', ' + rgba[1] + ', ' + rgba[2] + ', ' + rgba[3] + ')';
    } else {
      return 'rgb(' + rgba[0] + ', ' + rgba[1] + ', ' + rgba[2] + ')';
    }

  }

  // Parses a string and returns a valid color string when possible
  function parseInput(string, expand) {
    if(isRgb(string)) {
      // Returns a valid rgb(a) string
      return parseRgb(string);
    } else {
      return parseHex(string, expand);
    }
  }

  // Keeps value within min and max
  function keepWithin(value, min, max) {
    if(value < min) value = min;
    if(value > max) value = max;
    return value;
  }

  // Checks if a string is a valid RGB(A) string
  function isRgb(string) {
    var rgb = string.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);
    return (rgb && rgb.length === 4) ? true : false;
  }

  // Function to get alpha from a RGB(A) string
  function getAlpha(rgba) {
    rgba = rgba.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+(\.\d{1,2})?|\.\d{1,2})[\s+]?/i);
    return (rgba && rgba.length === 6) ? rgba[4] : '1';
  }

  // Converts an HSB object to an RGB object
  function hsb2rgb(hsb) {
    var rgb = {};
    var h = Math.round(hsb.h);
    var s = Math.round(hsb.s * 255 / 100);
    var v = Math.round(hsb.b * 255 / 100);
    if(s === 0) {
      rgb.r = rgb.g = rgb.b = v;
    } else {
      var t1 = v;
      var t2 = (255 - s) * v / 255;
      var t3 = (t1 - t2) * (h % 60) / 60;
      if(h === 360) h = 0;
      if(h < 60) { rgb.r = t1; rgb.b = t2; rgb.g = t2 + t3; }
      else if(h < 120) {rgb.g = t1; rgb.b = t2; rgb.r = t1 - t3; }
      else if(h < 180) {rgb.g = t1; rgb.r = t2; rgb.b = t2 + t3; }
      else if(h < 240) {rgb.b = t1; rgb.r = t2; rgb.g = t1 - t3; }
      else if(h < 300) {rgb.b = t1; rgb.g = t2; rgb.r = t2 + t3; }
      else if(h < 360) {rgb.r = t1; rgb.g = t2; rgb.b = t1 - t3; }
      else { rgb.r = 0; rgb.g = 0; rgb.b = 0; }
    }
    return {
      r: Math.round(rgb.r),
      g: Math.round(rgb.g),
      b: Math.round(rgb.b)
    };
  }

  // Converts an RGB string to a hex string
  function rgbString2hex(rgb){
    rgb = rgb.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);
    return (rgb && rgb.length === 4) ? '#' +
    ('0' + parseInt(rgb[1],10).toString(16)).slice(-2) +
    ('0' + parseInt(rgb[2],10).toString(16)).slice(-2) +
    ('0' + parseInt(rgb[3],10).toString(16)).slice(-2) : '';
  }

  // Converts an RGB object to a hex string
  function rgb2hex(rgb) {
    var hex = [
      rgb.r.toString(16),
      rgb.g.toString(16),
      rgb.b.toString(16)
    ];
    $.each(hex, function(nr, val) {
      if(val.length === 1) hex[nr] = '0' + val;
    });
    return '#' + hex.join('');
  }

  // Converts an HSB object to a hex string
  function hsb2hex(hsb) {
    return rgb2hex(hsb2rgb(hsb));
  }

  // Converts a hex string to an HSB object
  function hex2hsb(hex) {
    var hsb = rgb2hsb(hex2rgb(hex));
    if(hsb.s === 0) hsb.h = 360;
    return hsb;
  }

  // Converts an RGB object to an HSB object
  function rgb2hsb(rgb) {
    var hsb = { h: 0, s: 0, b: 0 };
    var min = Math.min(rgb.r, rgb.g, rgb.b);
    var max = Math.max(rgb.r, rgb.g, rgb.b);
    var delta = max - min;
    hsb.b = max;
    hsb.s = max !== 0 ? 255 * delta / max : 0;
    if(hsb.s !== 0) {
      if(rgb.r === max) {
        hsb.h = (rgb.g - rgb.b) / delta;
      } else if(rgb.g === max) {
        hsb.h = 2 + (rgb.b - rgb.r) / delta;
      } else {
        hsb.h = 4 + (rgb.r - rgb.g) / delta;
      }
    } else {
      hsb.h = -1;
    }
    hsb.h *= 60;
    if(hsb.h < 0) {
      hsb.h += 360;
    }
    hsb.s *= 100/255;
    hsb.b *= 100/255;
    return hsb;
  }

  // Converts a hex string to an RGB object
  function hex2rgb(hex) {
    hex = parseInt(((hex.indexOf('#') > -1) ? hex.substring(1) : hex), 16);
    return {
      r: hex >> 16,
      g: (hex & 0x00FF00) >> 8,
      b: (hex & 0x0000FF)
    };
  }

  // Handle events
  $([document])
    // Hide on clicks outside of the control
    .on('mousedown.minicolors touchstart.minicolors', function(event) {
      if(!$(event.target).parents().add(event.target).hasClass('minicolors')) {
        hide();
      }
    })
    // Start moving
    .on('mousedown.minicolors touchstart.minicolors', '.minicolors-grid, .minicolors-slider, .minicolors-opacity-slider', function(event) {
      var target = $(this);
      event.preventDefault();
      $(event.delegateTarget).data('minicolors-target', target);
      move(target, event, true);
    })
    // Move pickers
    .on('mousemove.minicolors touchmove.minicolors', function(event) {
      var target = $(event.delegateTarget).data('minicolors-target');
      if(target) move(target, event);
    })
    // Stop moving
    .on('mouseup.minicolors touchend.minicolors', function() {
      $(this).removeData('minicolors-target');
    })
    // Selected a swatch
    .on('click.minicolors', '.minicolors-swatches li', function(event) {
      event.preventDefault();
      var target = $(this), input = target.parents('.minicolors').find('.minicolors-input'), color = target.data('swatch-color');
      updateInput(input, color, getAlpha(color));
      updateFromInput(input);
    })
    // Show panel when swatch is clicked
    .on('mousedown.minicolors touchstart.minicolors', '.minicolors-input-swatch', function(event) {
      var input = $(this).parent().find('.minicolors-input');
      event.preventDefault();
      show(input);
    })
    // Show on focus
    .on('focus.minicolors', '.minicolors-input', function() {
      var input = $(this);
      if(!input.data('minicolors-initialized')) return;
      show(input);
    })
    // Update value on blur
    .on('blur.minicolors', '.minicolors-input', function() {
      var input = $(this);
      var settings = input.data('minicolors-settings');
      var keywords;
      var hex;
      var rgba;
      var swatchOpacity;
      var value;

      if(!input.data('minicolors-initialized')) return;

      // Get array of lowercase keywords
      keywords = !settings.keywords ? [] : $.map(settings.keywords.split(','), function(a) {
        return $.trim(a.toLowerCase());
      });

      // Set color string
      if(input.val() !== '' && $.inArray(input.val().toLowerCase(), keywords) > -1) {
        value = input.val();
      } else {
        // Get RGBA values for easy conversion
        if(isRgb(input.val())) {
          rgba = parseRgb(input.val(), true);
        } else {
          hex = parseHex(input.val(), true);
          rgba = hex ? hex2rgb(hex) : null;
        }

        // Convert to format
        if(rgba === null) {
          value = settings.defaultValue;
        } else if(settings.format === 'rgb') {
          value = settings.opacity ?
            parseRgb('rgba(' + rgba.r + ',' + rgba.g + ',' + rgba.b + ',' + input.attr('data-opacity') + ')') :
            parseRgb('rgb(' + rgba.r + ',' + rgba.g + ',' + rgba.b + ')');
        } else {
          value = rgb2hex(rgba);
        }
      }

      // Update swatch opacity
      swatchOpacity = settings.opacity ? input.attr('data-opacity') : 1;
      if(value.toLowerCase() === 'transparent') swatchOpacity = 0;
      input
        .closest('.minicolors')
        .find('.minicolors-input-swatch > span')
        .css('opacity', swatchOpacity);

      // Set input value
      input.val(value);

      // Is it blank?
      if(input.val() === '') input.val(parseInput(settings.defaultValue, true));

      // Adjust case
      input.val(convertCase(input.val(), settings.letterCase));

    })
    // Handle keypresses
    .on('keydown.minicolors', '.minicolors-input', function(event) {
      var input = $(this);
      if(!input.data('minicolors-initialized')) return;
      switch(event.which) {
      case 9: // tab
        hide();
        break;
      case 13: // enter
      case 27: // esc
        hide();
        input.blur();
        break;
      }
    })
    // Update on keyup
    .on('keyup.minicolors', '.minicolors-input', function() {
      var input = $(this);
      if(!input.data('minicolors-initialized')) return;
      updateFromInput(input, true);
    })
    // Update on paste
    .on('paste.minicolors', '.minicolors-input', function() {
      var input = $(this);
      if(!input.data('minicolors-initialized')) return;
      setTimeout(function() {
        updateFromInput(input, true);
      }, 1);
    });
}));

/*!
 * Cropper v4.0.0
 * https://github.com/fengyuanchen/cropper
 *
 * Copyright (c) 2014-2018 Chen Fengyuan
 * Released under the MIT license
 *
 * Date: 2018-04-01T06:27:27.267Z
 */

(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(require('jquery')) :
  typeof define === 'function' && define.amd ? define(['jquery'], factory) :
  (factory(global.jQuery));
}(this, (function ($) { 'use strict';

  $ = $ && $.hasOwnProperty('default') ? $['default'] : $;

  var IN_BROWSER = typeof window !== 'undefined';
  var WINDOW = IN_BROWSER ? window : {};
  var NAMESPACE = 'cropper';

  // Actions
  var ACTION_ALL = 'all';
  var ACTION_CROP = 'crop';
  var ACTION_MOVE = 'move';
  var ACTION_ZOOM = 'zoom';
  var ACTION_EAST = 'e';
  var ACTION_WEST = 'w';
  var ACTION_SOUTH = 's';
  var ACTION_NORTH = 'n';
  var ACTION_NORTH_EAST = 'ne';
  var ACTION_NORTH_WEST = 'nw';
  var ACTION_SOUTH_EAST = 'se';
  var ACTION_SOUTH_WEST = 'sw';

  // Classes
  var CLASS_CROP = NAMESPACE + '-crop';
  var CLASS_DISABLED = NAMESPACE + '-disabled';
  var CLASS_HIDDEN = NAMESPACE + '-hidden';
  var CLASS_HIDE = NAMESPACE + '-hide';
  var CLASS_INVISIBLE = NAMESPACE + '-invisible';
  var CLASS_MODAL = NAMESPACE + '-modal';
  var CLASS_MOVE = NAMESPACE + '-move';

  // Data keys
  var DATA_ACTION = 'action';
  var DATA_PREVIEW = 'preview';

  // Drag modes
  var DRAG_MODE_CROP = 'crop';
  var DRAG_MODE_MOVE = 'move';
  var DRAG_MODE_NONE = 'none';

  // Events
  var EVENT_CROP = 'crop';
  var EVENT_CROP_END = 'cropend';
  var EVENT_CROP_MOVE = 'cropmove';
  var EVENT_CROP_START = 'cropstart';
  var EVENT_DBLCLICK = 'dblclick';
  var EVENT_LOAD = 'load';
  var EVENT_POINTER_DOWN = WINDOW.PointerEvent ? 'pointerdown' : 'touchstart mousedown';
  var EVENT_POINTER_MOVE = WINDOW.PointerEvent ? 'pointermove' : 'touchmove mousemove';
  var EVENT_POINTER_UP = WINDOW.PointerEvent ? 'pointerup pointercancel' : 'touchend touchcancel mouseup';
  var EVENT_READY = 'ready';
  var EVENT_RESIZE = 'resize';
  var EVENT_WHEEL = 'wheel mousewheel DOMMouseScroll';
  var EVENT_ZOOM = 'zoom';

  // RegExps
  var REGEXP_ACTIONS = /^(?:e|w|s|n|se|sw|ne|nw|all|crop|move|zoom)$/;
  var REGEXP_DATA_URL = /^data:/;
  var REGEXP_DATA_URL_JPEG = /^data:image\/jpeg;base64,/;
  var REGEXP_TAG_NAME = /^(?:img|canvas)$/i;

  var DEFAULTS = {
    // Define the view mode of the cropper
    viewMode: 0, // 0, 1, 2, 3

    // Define the dragging mode of the cropper
    dragMode: DRAG_MODE_CROP, // 'crop', 'move' or 'none'

    // Define the aspect ratio of the crop box
    aspectRatio: NaN,

    // An object with the previous cropping result data
    data: null,

    // A selector for adding extra containers to preview
    preview: '',

    // Re-render the cropper when resize the window
    responsive: true,

    // Restore the cropped area after resize the window
    restore: true,

    // Check if the current image is a cross-origin image
    checkCrossOrigin: true,

    // Check the current image's Exif Orientation information
    checkOrientation: true,

    // Show the black modal
    modal: true,

    // Show the dashed lines for guiding
    guides: true,

    // Show the center indicator for guiding
    center: true,

    // Show the white modal to highlight the crop box
    highlight: true,

    // Show the grid background
    background: true,

    // Enable to crop the image automatically when initialize
    autoCrop: true,

    // Define the percentage of automatic cropping area when initializes
    autoCropArea: 0.8,

    // Enable to move the image
    movable: true,

    // Enable to rotate the image
    rotatable: true,

    // Enable to scale the image
    scalable: true,

    // Enable to zoom the image
    zoomable: true,

    // Enable to zoom the image by dragging touch
    zoomOnTouch: true,

    // Enable to zoom the image by wheeling mouse
    zoomOnWheel: true,

    // Define zoom ratio when zoom the image by wheeling mouse
    wheelZoomRatio: 0.1,

    // Enable to move the crop box
    cropBoxMovable: true,

    // Enable to resize the crop box
    cropBoxResizable: true,

    // Toggle drag mode between "crop" and "move" when click twice on the cropper
    toggleDragModeOnDblclick: true,

    // Size limitation
    minCanvasWidth: 0,
    minCanvasHeight: 0,
    minCropBoxWidth: 0,
    minCropBoxHeight: 0,
    minContainerWidth: 200,
    minContainerHeight: 100,

    // Shortcuts of events
    ready: null,
    cropstart: null,
    cropmove: null,
    cropend: null,
    crop: null,
    zoom: null
  };

  var TEMPLATE = '<div class="cropper-container" touch-action="none">' + '<div class="cropper-wrap-box">' + '<div class="cropper-canvas"></div>' + '</div>' + '<div class="cropper-drag-box"></div>' + '<div class="cropper-crop-box">' + '<span class="cropper-view-box"></span>' + '<span class="cropper-dashed dashed-h"></span>' + '<span class="cropper-dashed dashed-v"></span>' + '<span class="cropper-center"></span>' + '<span class="cropper-face"></span>' + '<span class="cropper-line line-e" data-action="e"></span>' + '<span class="cropper-line line-n" data-action="n"></span>' + '<span class="cropper-line line-w" data-action="w"></span>' + '<span class="cropper-line line-s" data-action="s"></span>' + '<span class="cropper-point point-e" data-action="e"></span>' + '<span class="cropper-point point-n" data-action="n"></span>' + '<span class="cropper-point point-w" data-action="w"></span>' + '<span class="cropper-point point-s" data-action="s"></span>' + '<span class="cropper-point point-ne" data-action="ne"></span>' + '<span class="cropper-point point-nw" data-action="nw"></span>' + '<span class="cropper-point point-sw" data-action="sw"></span>' + '<span class="cropper-point point-se" data-action="se"></span>' + '</div>' + '</div>';

  var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
  };

  var classCallCheck = function (instance, Constructor) {
    if (!(instance instanceof Constructor)) {
      throw new TypeError("Cannot call a class as a function");
    }
  };

  var createClass = function () {
    function defineProperties(target, props) {
      for (var i = 0; i < props.length; i++) {
        var descriptor = props[i];
        descriptor.enumerable = descriptor.enumerable || false;
        descriptor.configurable = true;
        if ("value" in descriptor) descriptor.writable = true;
        Object.defineProperty(target, descriptor.key, descriptor);
      }
    }

    return function (Constructor, protoProps, staticProps) {
      if (protoProps) defineProperties(Constructor.prototype, protoProps);
      if (staticProps) defineProperties(Constructor, staticProps);
      return Constructor;
    };
  }();

  var toConsumableArray = function (arr) {
    if (Array.isArray(arr)) {
      for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) arr2[i] = arr[i];

      return arr2;
    } else {
      return Array.from(arr);
    }
  };

  /**
   * Check if the given value is not a number.
   */
  var isNaN = Number.isNaN || WINDOW.isNaN;

  /**
   * Check if the given value is a number.
   * @param {*} value - The value to check.
   * @returns {boolean} Returns `true` if the given value is a number, else `false`.
   */
  function isNumber(value) {
    return typeof value === 'number' && !isNaN(value);
  }

  /**
   * Check if the given value is undefined.
   * @param {*} value - The value to check.
   * @returns {boolean} Returns `true` if the given value is undefined, else `false`.
   */
  function isUndefined(value) {
    return typeof value === 'undefined';
  }

  /**
   * Check if the given value is an object.
   * @param {*} value - The value to check.
   * @returns {boolean} Returns `true` if the given value is an object, else `false`.
   */
  function isObject(value) {
    return (typeof value === 'undefined' ? 'undefined' : _typeof(value)) === 'object' && value !== null;
  }

  var hasOwnProperty = Object.prototype.hasOwnProperty;

  /**
   * Check if the given value is a plain object.
   * @param {*} value - The value to check.
   * @returns {boolean} Returns `true` if the given value is a plain object, else `false`.
   */

  function isPlainObject(value) {
    if (!isObject(value)) {
      return false;
    }

    try {
      var _constructor = value.constructor;
      var prototype = _constructor.prototype;


      return _constructor && prototype && hasOwnProperty.call(prototype, 'isPrototypeOf');
    } catch (e) {
      return false;
    }
  }

  /**
   * Check if the given value is a function.
   * @param {*} value - The value to check.
   * @returns {boolean} Returns `true` if the given value is a function, else `false`.
   */
  function isFunction(value) {
    return typeof value === 'function';
  }

  /**
   * Iterate the given data.
   * @param {*} data - The data to iterate.
   * @param {Function} callback - The process function for each element.
   * @returns {*} The original data.
   */
  function forEach(data, callback) {
    if (data && isFunction(callback)) {
      if (Array.isArray(data) || isNumber(data.length) /* array-like */) {
          var length = data.length;

          var i = void 0;

          for (i = 0; i < length; i += 1) {
            if (callback.call(data, data[i], i, data) === false) {
              break;
            }
          }
        } else if (isObject(data)) {
        Object.keys(data).forEach(function (key) {
          callback.call(data, data[key], key, data);
        });
      }
    }

    return data;
  }

  /**
   * Extend the given object.
   * @param {*} obj - The object to be extended.
   * @param {*} args - The rest objects which will be merged to the first object.
   * @returns {Object} The extended object.
   */
  var assign = Object.assign || function assign(obj) {
    for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }

    if (isObject(obj) && args.length > 0) {
      args.forEach(function (arg) {
        if (isObject(arg)) {
          Object.keys(arg).forEach(function (key) {
            obj[key] = arg[key];
          });
        }
      });
    }

    return obj;
  };

  var REGEXP_DECIMALS = /\.\d*(?:0|9){12}\d*$/i;

  /**
   * Normalize decimal number.
   * Check out {@link http://0.30000000000000004.com/}
   * @param {number} value - The value to normalize.
   * @param {number} [times=100000000000] - The times for normalizing.
   * @returns {number} Returns the normalized number.
   */
  function normalizeDecimalNumber(value) {
    var times = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 100000000000;

    return REGEXP_DECIMALS.test(value) ? Math.round(value * times) / times : value;
  }

  var REGEXP_SUFFIX = /^(?:width|height|left|top|marginLeft|marginTop)$/;

  /**
   * Apply styles to the given element.
   * @param {Element} element - The target element.
   * @param {Object} styles - The styles for applying.
   */
  function setStyle(element, styles) {
    var style = element.style;


    forEach(styles, function (value, property) {
      if (REGEXP_SUFFIX.test(property) && isNumber(value)) {
        value += 'px';
      }

      style[property] = value;
    });
  }

  /**
   * Check if the given element has a special class.
   * @param {Element} element - The element to check.
   * @param {string} value - The class to search.
   * @returns {boolean} Returns `true` if the special class was found.
   */
  function hasClass(element, value) {
    return element.classList ? element.classList.contains(value) : element.className.indexOf(value) > -1;
  }

  /**
   * Add classes to the given element.
   * @param {Element} element - The target element.
   * @param {string} value - The classes to be added.
   */
  function addClass(element, value) {
    if (!value) {
      return;
    }

    if (isNumber(element.length)) {
      forEach(element, function (elem) {
        addClass(elem, value);
      });
      return;
    }

    if (element.classList) {
      element.classList.add(value);
      return;
    }

    var className = element.className.trim();

    if (!className) {
      element.className = value;
    } else if (className.indexOf(value) < 0) {
      element.className = className + ' ' + value;
    }
  }

  /**
   * Remove classes from the given element.
   * @param {Element} element - The target element.
   * @param {string} value - The classes to be removed.
   */
  function removeClass(element, value) {
    if (!value) {
      return;
    }

    if (isNumber(element.length)) {
      forEach(element, function (elem) {
        removeClass(elem, value);
      });
      return;
    }

    if (element.classList) {
      element.classList.remove(value);
      return;
    }

    if (element.className.indexOf(value) >= 0) {
      element.className = element.className.replace(value, '');
    }
  }

  /**
   * Add or remove classes from the given element.
   * @param {Element} element - The target element.
   * @param {string} value - The classes to be toggled.
   * @param {boolean} added - Add only.
   */
  function toggleClass(element, value, added) {
    if (!value) {
      return;
    }

    if (isNumber(element.length)) {
      forEach(element, function (elem) {
        toggleClass(elem, value, added);
      });
      return;
    }

    // IE10-11 doesn't support the second parameter of `classList.toggle`
    if (added) {
      addClass(element, value);
    } else {
      removeClass(element, value);
    }
  }

  var REGEXP_HYPHENATE = /([a-z\d])([A-Z])/g;

  /**
   * Transform the given string from camelCase to kebab-case
   * @param {string} value - The value to transform.
   * @returns {string} The transformed value.
   */
  function hyphenate(value) {
    return value.replace(REGEXP_HYPHENATE, '$1-$2').toLowerCase();
  }

  /**
   * Get data from the given element.
   * @param {Element} element - The target element.
   * @param {string} name - The data key to get.
   * @returns {string} The data value.
   */
  function getData(element, name) {
    if (isObject(element[name])) {
      return element[name];
    } else if (element.dataset) {
      return element.dataset[name];
    }

    return element.getAttribute('data-' + hyphenate(name));
  }

  /**
   * Set data to the given element.
   * @param {Element} element - The target element.
   * @param {string} name - The data key to set.
   * @param {string} data - The data value.
   */
  function setData(element, name, data) {
    if (isObject(data)) {
      element[name] = data;
    } else if (element.dataset) {
      element.dataset[name] = data;
    } else {
      element.setAttribute('data-' + hyphenate(name), data);
    }
  }

  /**
   * Remove data from the given element.
   * @param {Element} element - The target element.
   * @param {string} name - The data key to remove.
   */
  function removeData(element, name) {
    if (isObject(element[name])) {
      try {
        delete element[name];
      } catch (e) {
        element[name] = undefined;
      }
    } else if (element.dataset) {
      // #128 Safari not allows to delete dataset property
      try {
        delete element.dataset[name];
      } catch (e) {
        element.dataset[name] = undefined;
      }
    } else {
      element.removeAttribute('data-' + hyphenate(name));
    }
  }

  var REGEXP_SPACES = /\s\s*/;
  var onceSupported = function () {
    var supported = false;

    if (IN_BROWSER) {
      var once = false;
      var listener = function listener() {};
      var options = Object.defineProperty({}, 'once', {
        get: function get$$1() {
          supported = true;
          return once;
        },


        /**
         * This setter can fix a `TypeError` in strict mode
         * {@link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Errors/Getter_only}
         * @param {boolean} value - The value to set
         */
        set: function set$$1(value) {
          once = value;
        }
      });

      WINDOW.addEventListener('test', listener, options);
      WINDOW.removeEventListener('test', listener, options);
    }

    return supported;
  }();

  /**
   * Remove event listener from the target element.
   * @param {Element} element - The event target.
   * @param {string} type - The event type(s).
   * @param {Function} listener - The event listener.
   * @param {Object} options - The event options.
   */
  function removeListener(element, type, listener) {
    var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};

    var handler = listener;

    type.trim().split(REGEXP_SPACES).forEach(function (event) {
      if (!onceSupported) {
        var listeners = element.listeners;


        if (listeners && listeners[event] && listeners[event][listener]) {
          handler = listeners[event][listener];
          delete listeners[event][listener];

          if (Object.keys(listeners[event]).length === 0) {
            delete listeners[event];
          }

          if (Object.keys(listeners).length === 0) {
            delete element.listeners;
          }
        }
      }

      element.removeEventListener(event, handler, options);
    });
  }

  /**
   * Add event listener to the target element.
   * @param {Element} element - The event target.
   * @param {string} type - The event type(s).
   * @param {Function} listener - The event listener.
   * @param {Object} options - The event options.
   */
  function addListener(element, type, listener) {
    var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};

    var _handler = listener;

    type.trim().split(REGEXP_SPACES).forEach(function (event) {
      if (options.once && !onceSupported) {
        var _element$listeners = element.listeners,
            listeners = _element$listeners === undefined ? {} : _element$listeners;


        _handler = function handler() {
          for (var _len2 = arguments.length, args = Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
            args[_key2] = arguments[_key2];
          }

          delete listeners[event][listener];
          element.removeEventListener(event, _handler, options);
          listener.apply(element, args);
        };

        if (!listeners[event]) {
          listeners[event] = {};
        }

        if (listeners[event][listener]) {
          element.removeEventListener(event, listeners[event][listener], options);
        }

        listeners[event][listener] = _handler;
        element.listeners = listeners;
      }

      element.addEventListener(event, _handler, options);
    });
  }

  /**
   * Dispatch event on the target element.
   * @param {Element} element - The event target.
   * @param {string} type - The event type(s).
   * @param {Object} data - The additional event data.
   * @returns {boolean} Indicate if the event is default prevented or not.
   */
  function dispatchEvent(element, type, data) {
    var event = void 0;

    // Event and CustomEvent on IE9-11 are global objects, not constructors
    if (isFunction(Event) && isFunction(CustomEvent)) {
      event = new CustomEvent(type, {
        detail: data,
        bubbles: true,
        cancelable: true
      });
    } else {
      event = document.createEvent('CustomEvent');
      event.initCustomEvent(type, true, true, data);
    }

    return element.dispatchEvent(event);
  }

  /**
   * Get the offset base on the document.
   * @param {Element} element - The target element.
   * @returns {Object} The offset data.
   */
  function getOffset(element) {
    var box = element.getBoundingClientRect();

    return {
      left: box.left + (window.pageXOffset - document.documentElement.clientLeft),
      top: box.top + (window.pageYOffset - document.documentElement.clientTop)
    };
  }

  var location = WINDOW.location;

  var REGEXP_ORIGINS = /^(https?:)\/\/([^:/?#]+):?(\d*)/i;

  /**
   * Check if the given URL is a cross origin URL.
   * @param {string} url - The target URL.
   * @returns {boolean} Returns `true` if the given URL is a cross origin URL, else `false`.
   */
  function isCrossOriginURL(url) {
    var parts = url.match(REGEXP_ORIGINS);

    return parts && (parts[1] !== location.protocol || parts[2] !== location.hostname || parts[3] !== location.port);
  }

  /**
   * Add timestamp to the given URL.
   * @param {string} url - The target URL.
   * @returns {string} The result URL.
   */
  function addTimestamp(url) {
    var timestamp = 'timestamp=' + new Date().getTime();

    return url + (url.indexOf('?') === -1 ? '?' : '&') + timestamp;
  }

  /**
   * Get transforms base on the given object.
   * @param {Object} obj - The target object.
   * @returns {string} A string contains transform values.
   */
  function getTransforms(_ref) {
    var rotate = _ref.rotate,
        scaleX = _ref.scaleX,
        scaleY = _ref.scaleY,
        translateX = _ref.translateX,
        translateY = _ref.translateY;

    var values = [];

    if (isNumber(translateX) && translateX !== 0) {
      values.push('translateX(' + translateX + 'px)');
    }

    if (isNumber(translateY) && translateY !== 0) {
      values.push('translateY(' + translateY + 'px)');
    }

    // Rotate should come first before scale to match orientation transform
    if (isNumber(rotate) && rotate !== 0) {
      values.push('rotate(' + rotate + 'deg)');
    }

    if (isNumber(scaleX) && scaleX !== 1) {
      values.push('scaleX(' + scaleX + ')');
    }

    if (isNumber(scaleY) && scaleY !== 1) {
      values.push('scaleY(' + scaleY + ')');
    }

    var transform = values.length ? values.join(' ') : 'none';

    return {
      WebkitTransform: transform,
      msTransform: transform,
      transform: transform
    };
  }

  /**
   * Get the max ratio of a group of pointers.
   * @param {string} pointers - The target pointers.
   * @returns {number} The result ratio.
   */
  function getMaxZoomRatio(pointers) {
    var pointers2 = assign({}, pointers);
    var ratios = [];

    forEach(pointers, function (pointer, pointerId) {
      delete pointers2[pointerId];

      forEach(pointers2, function (pointer2) {
        var x1 = Math.abs(pointer.startX - pointer2.startX);
        var y1 = Math.abs(pointer.startY - pointer2.startY);
        var x2 = Math.abs(pointer.endX - pointer2.endX);
        var y2 = Math.abs(pointer.endY - pointer2.endY);
        var z1 = Math.sqrt(x1 * x1 + y1 * y1);
        var z2 = Math.sqrt(x2 * x2 + y2 * y2);
        var ratio = (z2 - z1) / z1;

        ratios.push(ratio);
      });
    });

    ratios.sort(function (a, b) {
      return Math.abs(a) < Math.abs(b);
    });

    return ratios[0];
  }

  /**
   * Get a pointer from an event object.
   * @param {Object} event - The target event object.
   * @param {boolean} endOnly - Indicates if only returns the end point coordinate or not.
   * @returns {Object} The result pointer contains start and/or end point coordinates.
   */
  function getPointer(_ref2, endOnly) {
    var pageX = _ref2.pageX,
        pageY = _ref2.pageY;

    var end = {
      endX: pageX,
      endY: pageY
    };

    return endOnly ? end : assign({
      startX: pageX,
      startY: pageY
    }, end);
  }

  /**
   * Get the center point coordinate of a group of pointers.
   * @param {Object} pointers - The target pointers.
   * @returns {Object} The center point coordinate.
   */
  function getPointersCenter(pointers) {
    var pageX = 0;
    var pageY = 0;
    var count = 0;

    forEach(pointers, function (_ref3) {
      var startX = _ref3.startX,
          startY = _ref3.startY;

      pageX += startX;
      pageY += startY;
      count += 1;
    });

    pageX /= count;
    pageY /= count;

    return {
      pageX: pageX,
      pageY: pageY
    };
  }

  /**
   * Check if the given value is a finite number.
   */
  var isFinite = Number.isFinite || WINDOW.isFinite;

  /**
   * Get the max sizes in a rectangle under the given aspect ratio.
   * @param {Object} data - The original sizes.
   * @param {string} [type='contain'] - The adjust type.
   * @returns {Object} The result sizes.
   */
  function getAdjustedSizes(_ref4) // or 'cover'
  {
    var aspectRatio = _ref4.aspectRatio,
        height = _ref4.height,
        width = _ref4.width;
    var type = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'contain';

    var isValidNumber = function isValidNumber(value) {
      return isFinite(value) && value > 0;
    };

    if (isValidNumber(width) && isValidNumber(height)) {
      var adjustedWidth = height * aspectRatio;

      if (type === 'contain' && adjustedWidth > width || type === 'cover' && adjustedWidth < width) {
        height = width / aspectRatio;
      } else {
        width = height * aspectRatio;
      }
    } else if (isValidNumber(width)) {
      height = width / aspectRatio;
    } else if (isValidNumber(height)) {
      width = height * aspectRatio;
    }

    return {
      width: width,
      height: height
    };
  }

  /**
   * Get the new sizes of a rectangle after rotated.
   * @param {Object} data - The original sizes.
   * @returns {Object} The result sizes.
   */
  function getRotatedSizes(_ref5) {
    var width = _ref5.width,
        height = _ref5.height,
        degree = _ref5.degree;

    degree = Math.abs(degree) % 180;

    if (degree === 90) {
      return {
        width: height,
        height: width
      };
    }

    var arc = degree % 90 * Math.PI / 180;
    var sinArc = Math.sin(arc);
    var cosArc = Math.cos(arc);
    var newWidth = width * cosArc + height * sinArc;
    var newHeight = width * sinArc + height * cosArc;

    return degree > 90 ? {
      width: newHeight,
      height: newWidth
    } : {
      width: newWidth,
      height: newHeight
    };
  }

  /**
   * Get a canvas which drew the given image.
   * @param {HTMLImageElement} image - The image for drawing.
   * @param {Object} imageData - The image data.
   * @param {Object} canvasData - The canvas data.
   * @param {Object} options - The options.
   * @returns {HTMLCanvasElement} The result canvas.
   */
  function getSourceCanvas(image, _ref6, _ref7, _ref8) {
    var imageAspectRatio = _ref6.aspectRatio,
        imageNaturalWidth = _ref6.naturalWidth,
        imageNaturalHeight = _ref6.naturalHeight,
        _ref6$rotate = _ref6.rotate,
        rotate = _ref6$rotate === undefined ? 0 : _ref6$rotate,
        _ref6$scaleX = _ref6.scaleX,
        scaleX = _ref6$scaleX === undefined ? 1 : _ref6$scaleX,
        _ref6$scaleY = _ref6.scaleY,
        scaleY = _ref6$scaleY === undefined ? 1 : _ref6$scaleY;
    var aspectRatio = _ref7.aspectRatio,
        naturalWidth = _ref7.naturalWidth,
        naturalHeight = _ref7.naturalHeight;
    var _ref8$fillColor = _ref8.fillColor,
        fillColor = _ref8$fillColor === undefined ? 'transparent' : _ref8$fillColor,
        _ref8$imageSmoothingE = _ref8.imageSmoothingEnabled,
        imageSmoothingEnabled = _ref8$imageSmoothingE === undefined ? true : _ref8$imageSmoothingE,
        _ref8$imageSmoothingQ = _ref8.imageSmoothingQuality,
        imageSmoothingQuality = _ref8$imageSmoothingQ === undefined ? 'low' : _ref8$imageSmoothingQ,
        _ref8$maxWidth = _ref8.maxWidth,
        maxWidth = _ref8$maxWidth === undefined ? Infinity : _ref8$maxWidth,
        _ref8$maxHeight = _ref8.maxHeight,
        maxHeight = _ref8$maxHeight === undefined ? Infinity : _ref8$maxHeight,
        _ref8$minWidth = _ref8.minWidth,
        minWidth = _ref8$minWidth === undefined ? 0 : _ref8$minWidth,
        _ref8$minHeight = _ref8.minHeight,
        minHeight = _ref8$minHeight === undefined ? 0 : _ref8$minHeight;

    var canvas = document.createElement('canvas');
    var context = canvas.getContext('2d');
    var maxSizes = getAdjustedSizes({
      aspectRatio: aspectRatio,
      width: maxWidth,
      height: maxHeight
    });
    var minSizes = getAdjustedSizes({
      aspectRatio: aspectRatio,
      width: minWidth,
      height: minHeight
    }, 'cover');
    var width = Math.min(maxSizes.width, Math.max(minSizes.width, naturalWidth));
    var height = Math.min(maxSizes.height, Math.max(minSizes.height, naturalHeight));

    // Note: should always use image's natural sizes for drawing as
    // imageData.naturalWidth === canvasData.naturalHeight when rotate % 180 === 90
    var destMaxSizes = getAdjustedSizes({
      aspectRatio: imageAspectRatio,
      width: maxWidth,
      height: maxHeight
    });
    var destMinSizes = getAdjustedSizes({
      aspectRatio: imageAspectRatio,
      width: minWidth,
      height: minHeight
    }, 'cover');
    var destWidth = Math.min(destMaxSizes.width, Math.max(destMinSizes.width, imageNaturalWidth));
    var destHeight = Math.min(destMaxSizes.height, Math.max(destMinSizes.height, imageNaturalHeight));
    var params = [-destWidth / 2, -destHeight / 2, destWidth, destHeight];

    canvas.width = normalizeDecimalNumber(width);
    canvas.height = normalizeDecimalNumber(height);
    context.fillStyle = fillColor;
    context.fillRect(0, 0, width, height);
    context.save();
    context.translate(width / 2, height / 2);
    context.rotate(rotate * Math.PI / 180);
    context.scale(scaleX, scaleY);
    context.imageSmoothingEnabled = imageSmoothingEnabled;
    context.imageSmoothingQuality = imageSmoothingQuality;
    context.drawImage.apply(context, [image].concat(toConsumableArray(params.map(function (param) {
      return Math.floor(normalizeDecimalNumber(param));
    }))));
    context.restore();
    return canvas;
  }

  var fromCharCode = String.fromCharCode;

  /**
   * Get string from char code in data view.
   * @param {DataView} dataView - The data view for read.
   * @param {number} start - The start index.
   * @param {number} length - The read length.
   * @returns {string} The read result.
   */

  function getStringFromCharCode(dataView, start, length) {
    var str = '';
    var i = void 0;

    length += start;

    for (i = start; i < length; i += 1) {
      str += fromCharCode(dataView.getUint8(i));
    }

    return str;
  }

  var REGEXP_DATA_URL_HEAD = /^data:.*,/;

  /**
   * Transform Data URL to array buffer.
   * @param {string} dataURL - The Data URL to transform.
   * @returns {ArrayBuffer} The result array buffer.
   */
  function dataURLToArrayBuffer(dataURL) {
    var base64 = dataURL.replace(REGEXP_DATA_URL_HEAD, '');
    var binary = atob(base64);
    var arrayBuffer = new ArrayBuffer(binary.length);
    var uint8 = new Uint8Array(arrayBuffer);

    forEach(uint8, function (value, i) {
      uint8[i] = binary.charCodeAt(i);
    });

    return arrayBuffer;
  }

  /**
   * Transform array buffer to Data URL.
   * @param {ArrayBuffer} arrayBuffer - The array buffer to transform.
   * @param {string} mimeType - The mime type of the Data URL.
   * @returns {string} The result Data URL.
   */
  function arrayBufferToDataURL(arrayBuffer, mimeType) {
    var uint8 = new Uint8Array(arrayBuffer);
    var data = '';

    // TypedArray.prototype.forEach is not supported in some browsers.
    forEach(uint8, function (value) {
      data += fromCharCode(value);
    });

    return 'data:' + mimeType + ';base64,' + btoa(data);
  }

  /**
   * Get orientation value from given array buffer.
   * @param {ArrayBuffer} arrayBuffer - The array buffer to read.
   * @returns {number} The read orientation value.
   */
  function getOrientation(arrayBuffer) {
    var dataView = new DataView(arrayBuffer);
    var orientation = void 0;
    var littleEndian = void 0;
    var app1Start = void 0;
    var ifdStart = void 0;

    // Only handle JPEG image (start by 0xFFD8)
    if (dataView.getUint8(0) === 0xFF && dataView.getUint8(1) === 0xD8) {
      var length = dataView.byteLength;
      var offset = 2;

      while (offset < length) {
        if (dataView.getUint8(offset) === 0xFF && dataView.getUint8(offset + 1) === 0xE1) {
          app1Start = offset;
          break;
        }

        offset += 1;
      }
    }

    if (app1Start) {
      var exifIDCode = app1Start + 4;
      var tiffOffset = app1Start + 10;

      if (getStringFromCharCode(dataView, exifIDCode, 4) === 'Exif') {
        var endianness = dataView.getUint16(tiffOffset);

        littleEndian = endianness === 0x4949;

        if (littleEndian || endianness === 0x4D4D /* bigEndian */) {
            if (dataView.getUint16(tiffOffset + 2, littleEndian) === 0x002A) {
              var firstIFDOffset = dataView.getUint32(tiffOffset + 4, littleEndian);

              if (firstIFDOffset >= 0x00000008) {
                ifdStart = tiffOffset + firstIFDOffset;
              }
            }
          }
      }
    }

    if (ifdStart) {
      var _length = dataView.getUint16(ifdStart, littleEndian);
      var _offset = void 0;
      var i = void 0;

      for (i = 0; i < _length; i += 1) {
        _offset = ifdStart + i * 12 + 2;

        if (dataView.getUint16(_offset, littleEndian) === 0x0112 /* Orientation */) {
            // 8 is the offset of the current tag's value
            _offset += 8;

            // Get the original orientation value
            orientation = dataView.getUint16(_offset, littleEndian);

            // Override the orientation with its default value
            dataView.setUint16(_offset, 1, littleEndian);
            break;
          }
      }
    }

    return orientation;
  }

  /**
   * Parse Exif Orientation value.
   * @param {number} orientation - The orientation to parse.
   * @returns {Object} The parsed result.
   */
  function parseOrientation(orientation) {
    var rotate = 0;
    var scaleX = 1;
    var scaleY = 1;

    switch (orientation) {
      // Flip horizontal
      case 2:
        scaleX = -1;
        break;

      // Rotate left 180°
      case 3:
        rotate = -180;
        break;

      // Flip vertical
      case 4:
        scaleY = -1;
        break;

      // Flip vertical and rotate right 90°
      case 5:
        rotate = 90;
        scaleY = -1;
        break;

      // Rotate right 90°
      case 6:
        rotate = 90;
        break;

      // Flip horizontal and rotate right 90°
      case 7:
        rotate = 90;
        scaleX = -1;
        break;

      // Rotate left 90°
      case 8:
        rotate = -90;
        break;

      default:
    }

    return {
      rotate: rotate,
      scaleX: scaleX,
      scaleY: scaleY
    };
  }

  var render = {
    render: function render() {
      this.initContainer();
      this.initCanvas();
      this.initCropBox();
      this.renderCanvas();

      if (this.cropped) {
        this.renderCropBox();
      }
    },
    initContainer: function initContainer() {
      var element = this.element,
          options = this.options,
          container = this.container,
          cropper = this.cropper;


      addClass(cropper, CLASS_HIDDEN);
      removeClass(element, CLASS_HIDDEN);

      var containerData = {
        width: Math.max(container.offsetWidth, Number(options.minContainerWidth) || 200),
        height: Math.max(container.offsetHeight, Number(options.minContainerHeight) || 100)
      };

      this.containerData = containerData;

      setStyle(cropper, {
        width: containerData.width,
        height: containerData.height
      });

      addClass(element, CLASS_HIDDEN);
      removeClass(cropper, CLASS_HIDDEN);
    },


    // Canvas (image wrapper)
    initCanvas: function initCanvas() {
      var containerData = this.containerData,
          imageData = this.imageData;
      var viewMode = this.options.viewMode;

      var rotated = Math.abs(imageData.rotate) % 180 === 90;
      var naturalWidth = rotated ? imageData.naturalHeight : imageData.naturalWidth;
      var naturalHeight = rotated ? imageData.naturalWidth : imageData.naturalHeight;
      var aspectRatio = naturalWidth / naturalHeight;
      var canvasWidth = containerData.width;
      var canvasHeight = containerData.height;

      if (containerData.height * aspectRatio > containerData.width) {
        if (viewMode === 3) {
          canvasWidth = containerData.height * aspectRatio;
        } else {
          canvasHeight = containerData.width / aspectRatio;
        }
      } else if (viewMode === 3) {
        canvasHeight = containerData.width / aspectRatio;
      } else {
        canvasWidth = containerData.height * aspectRatio;
      }

      var canvasData = {
        aspectRatio: aspectRatio,
        naturalWidth: naturalWidth,
        naturalHeight: naturalHeight,
        width: canvasWidth,
        height: canvasHeight
      };

      canvasData.left = (containerData.width - canvasWidth) / 2;
      canvasData.top = (containerData.height - canvasHeight) / 2;
      canvasData.oldLeft = canvasData.left;
      canvasData.oldTop = canvasData.top;

      this.canvasData = canvasData;
      this.limited = viewMode === 1 || viewMode === 2;
      this.limitCanvas(true, true);
      this.initialImageData = assign({}, imageData);
      this.initialCanvasData = assign({}, canvasData);
    },
    limitCanvas: function limitCanvas(sizeLimited, positionLimited) {
      var options = this.options,
          containerData = this.containerData,
          canvasData = this.canvasData,
          cropBoxData = this.cropBoxData;
      var viewMode = options.viewMode;
      var aspectRatio = canvasData.aspectRatio;

      var cropped = this.cropped && cropBoxData;

      if (sizeLimited) {
        var minCanvasWidth = Number(options.minCanvasWidth) || 0;
        var minCanvasHeight = Number(options.minCanvasHeight) || 0;

        if (viewMode > 1) {
          minCanvasWidth = Math.max(minCanvasWidth, containerData.width);
          minCanvasHeight = Math.max(minCanvasHeight, containerData.height);

          if (viewMode === 3) {
            if (minCanvasHeight * aspectRatio > minCanvasWidth) {
              minCanvasWidth = minCanvasHeight * aspectRatio;
            } else {
              minCanvasHeight = minCanvasWidth / aspectRatio;
            }
          }
        } else if (viewMode > 0) {
          if (minCanvasWidth) {
            minCanvasWidth = Math.max(minCanvasWidth, cropped ? cropBoxData.width : 0);
          } else if (minCanvasHeight) {
            minCanvasHeight = Math.max(minCanvasHeight, cropped ? cropBoxData.height : 0);
          } else if (cropped) {
            minCanvasWidth = cropBoxData.width;
            minCanvasHeight = cropBoxData.height;

            if (minCanvasHeight * aspectRatio > minCanvasWidth) {
              minCanvasWidth = minCanvasHeight * aspectRatio;
            } else {
              minCanvasHeight = minCanvasWidth / aspectRatio;
            }
          }
        }

        var _getAdjustedSizes = getAdjustedSizes({
          aspectRatio: aspectRatio,
          width: minCanvasWidth,
          height: minCanvasHeight
        });

        minCanvasWidth = _getAdjustedSizes.width;
        minCanvasHeight = _getAdjustedSizes.height;


        canvasData.minWidth = minCanvasWidth;
        canvasData.minHeight = minCanvasHeight;
        canvasData.maxWidth = Infinity;
        canvasData.maxHeight = Infinity;
      }

      if (positionLimited) {
        if (viewMode) {
          var newCanvasLeft = containerData.width - canvasData.width;
          var newCanvasTop = containerData.height - canvasData.height;

          canvasData.minLeft = Math.min(0, newCanvasLeft);
          canvasData.minTop = Math.min(0, newCanvasTop);
          canvasData.maxLeft = Math.max(0, newCanvasLeft);
          canvasData.maxTop = Math.max(0, newCanvasTop);

          if (cropped && this.limited) {
            canvasData.minLeft = Math.min(cropBoxData.left, cropBoxData.left + (cropBoxData.width - canvasData.width));
            canvasData.minTop = Math.min(cropBoxData.top, cropBoxData.top + (cropBoxData.height - canvasData.height));
            canvasData.maxLeft = cropBoxData.left;
            canvasData.maxTop = cropBoxData.top;

            if (viewMode === 2) {
              if (canvasData.width >= containerData.width) {
                canvasData.minLeft = Math.min(0, newCanvasLeft);
                canvasData.maxLeft = Math.max(0, newCanvasLeft);
              }

              if (canvasData.height >= containerData.height) {
                canvasData.minTop = Math.min(0, newCanvasTop);
                canvasData.maxTop = Math.max(0, newCanvasTop);
              }
            }
          }
        } else {
          canvasData.minLeft = -canvasData.width;
          canvasData.minTop = -canvasData.height;
          canvasData.maxLeft = containerData.width;
          canvasData.maxTop = containerData.height;
        }
      }
    },
    renderCanvas: function renderCanvas(changed, transformed) {
      var canvasData = this.canvasData,
          imageData = this.imageData;


      if (transformed) {
        var _getRotatedSizes = getRotatedSizes({
          width: imageData.naturalWidth * Math.abs(imageData.scaleX || 1),
          height: imageData.naturalHeight * Math.abs(imageData.scaleY || 1),
          degree: imageData.rotate || 0
        }),
            naturalWidth = _getRotatedSizes.width,
            naturalHeight = _getRotatedSizes.height;

        var width = canvasData.width * (naturalWidth / canvasData.naturalWidth);
        var height = canvasData.height * (naturalHeight / canvasData.naturalHeight);

        canvasData.left -= (width - canvasData.width) / 2;
        canvasData.top -= (height - canvasData.height) / 2;
        canvasData.width = width;
        canvasData.height = height;
        canvasData.aspectRatio = naturalWidth / naturalHeight;
        canvasData.naturalWidth = naturalWidth;
        canvasData.naturalHeight = naturalHeight;
        this.limitCanvas(true, false);
      }

      if (canvasData.width > canvasData.maxWidth || canvasData.width < canvasData.minWidth) {
        canvasData.left = canvasData.oldLeft;
      }

      if (canvasData.height > canvasData.maxHeight || canvasData.height < canvasData.minHeight) {
        canvasData.top = canvasData.oldTop;
      }

      canvasData.width = Math.min(Math.max(canvasData.width, canvasData.minWidth), canvasData.maxWidth);
      canvasData.height = Math.min(Math.max(canvasData.height, canvasData.minHeight), canvasData.maxHeight);

      this.limitCanvas(false, true);

      canvasData.left = Math.min(Math.max(canvasData.left, canvasData.minLeft), canvasData.maxLeft);
      canvasData.top = Math.min(Math.max(canvasData.top, canvasData.minTop), canvasData.maxTop);
      canvasData.oldLeft = canvasData.left;
      canvasData.oldTop = canvasData.top;

      setStyle(this.canvas, assign({
        width: canvasData.width,
        height: canvasData.height
      }, getTransforms({
        translateX: canvasData.left,
        translateY: canvasData.top
      })));

      this.renderImage(changed);

      if (this.cropped && this.limited) {
        this.limitCropBox(true, true);
      }
    },
    renderImage: function renderImage(changed) {
      var canvasData = this.canvasData,
          imageData = this.imageData;

      var width = imageData.naturalWidth * (canvasData.width / canvasData.naturalWidth);
      var height = imageData.naturalHeight * (canvasData.height / canvasData.naturalHeight);

      assign(imageData, {
        width: width,
        height: height,
        left: (canvasData.width - width) / 2,
        top: (canvasData.height - height) / 2
      });
      setStyle(this.image, assign({
        width: imageData.width,
        height: imageData.height
      }, getTransforms(assign({
        translateX: imageData.left,
        translateY: imageData.top
      }, imageData))));

      if (changed) {
        this.output();
      }
    },
    initCropBox: function initCropBox() {
      var options = this.options,
          canvasData = this.canvasData;
      var aspectRatio = options.aspectRatio;

      var autoCropArea = Number(options.autoCropArea) || 0.8;
      var cropBoxData = {
        width: canvasData.width,
        height: canvasData.height
      };

      if (aspectRatio) {
        if (canvasData.height * aspectRatio > canvasData.width) {
          cropBoxData.height = cropBoxData.width / aspectRatio;
        } else {
          cropBoxData.width = cropBoxData.height * aspectRatio;
        }
      }

      this.cropBoxData = cropBoxData;
      this.limitCropBox(true, true);

      // Initialize auto crop area
      cropBoxData.width = Math.min(Math.max(cropBoxData.width, cropBoxData.minWidth), cropBoxData.maxWidth);
      cropBoxData.height = Math.min(Math.max(cropBoxData.height, cropBoxData.minHeight), cropBoxData.maxHeight);

      // The width/height of auto crop area must large than "minWidth/Height"
      cropBoxData.width = Math.max(cropBoxData.minWidth, cropBoxData.width * autoCropArea);
      cropBoxData.height = Math.max(cropBoxData.minHeight, cropBoxData.height * autoCropArea);
      cropBoxData.left = canvasData.left + (canvasData.width - cropBoxData.width) / 2;
      cropBoxData.top = canvasData.top + (canvasData.height - cropBoxData.height) / 2;
      cropBoxData.oldLeft = cropBoxData.left;
      cropBoxData.oldTop = cropBoxData.top;

      this.initialCropBoxData = assign({}, cropBoxData);
    },
    limitCropBox: function limitCropBox(sizeLimited, positionLimited) {
      var options = this.options,
          containerData = this.containerData,
          canvasData = this.canvasData,
          cropBoxData = this.cropBoxData,
          limited = this.limited;
      var aspectRatio = options.aspectRatio;


      if (sizeLimited) {
        var minCropBoxWidth = Number(options.minCropBoxWidth) || 0;
        var minCropBoxHeight = Number(options.minCropBoxHeight) || 0;
        var maxCropBoxWidth = Math.min(containerData.width, limited ? canvasData.width : containerData.width);
        var maxCropBoxHeight = Math.min(containerData.height, limited ? canvasData.height : containerData.height);

        // The min/maxCropBoxWidth/Height must be less than container's width/height
        minCropBoxWidth = Math.min(minCropBoxWidth, containerData.width);
        minCropBoxHeight = Math.min(minCropBoxHeight, containerData.height);

        if (aspectRatio) {
          if (minCropBoxWidth && minCropBoxHeight) {
            if (minCropBoxHeight * aspectRatio > minCropBoxWidth) {
              minCropBoxHeight = minCropBoxWidth / aspectRatio;
            } else {
              minCropBoxWidth = minCropBoxHeight * aspectRatio;
            }
          } else if (minCropBoxWidth) {
            minCropBoxHeight = minCropBoxWidth / aspectRatio;
          } else if (minCropBoxHeight) {
            minCropBoxWidth = minCropBoxHeight * aspectRatio;
          }

          if (maxCropBoxHeight * aspectRatio > maxCropBoxWidth) {
            maxCropBoxHeight = maxCropBoxWidth / aspectRatio;
          } else {
            maxCropBoxWidth = maxCropBoxHeight * aspectRatio;
          }
        }

        // The minWidth/Height must be less than maxWidth/Height
        cropBoxData.minWidth = Math.min(minCropBoxWidth, maxCropBoxWidth);
        cropBoxData.minHeight = Math.min(minCropBoxHeight, maxCropBoxHeight);
        cropBoxData.maxWidth = maxCropBoxWidth;
        cropBoxData.maxHeight = maxCropBoxHeight;
      }

      if (positionLimited) {
        if (limited) {
          cropBoxData.minLeft = Math.max(0, canvasData.left);
          cropBoxData.minTop = Math.max(0, canvasData.top);
          cropBoxData.maxLeft = Math.min(containerData.width, canvasData.left + canvasData.width) - cropBoxData.width;
          cropBoxData.maxTop = Math.min(containerData.height, canvasData.top + canvasData.height) - cropBoxData.height;
        } else {
          cropBoxData.minLeft = 0;
          cropBoxData.minTop = 0;
          cropBoxData.maxLeft = containerData.width - cropBoxData.width;
          cropBoxData.maxTop = containerData.height - cropBoxData.height;
        }
      }
    },
    renderCropBox: function renderCropBox() {
      var options = this.options,
          containerData = this.containerData,
          cropBoxData = this.cropBoxData;


      if (cropBoxData.width > cropBoxData.maxWidth || cropBoxData.width < cropBoxData.minWidth) {
        cropBoxData.left = cropBoxData.oldLeft;
      }

      if (cropBoxData.height > cropBoxData.maxHeight || cropBoxData.height < cropBoxData.minHeight) {
        cropBoxData.top = cropBoxData.oldTop;
      }

      cropBoxData.width = Math.min(Math.max(cropBoxData.width, cropBoxData.minWidth), cropBoxData.maxWidth);
      cropBoxData.height = Math.min(Math.max(cropBoxData.height, cropBoxData.minHeight), cropBoxData.maxHeight);

      this.limitCropBox(false, true);

      cropBoxData.left = Math.min(Math.max(cropBoxData.left, cropBoxData.minLeft), cropBoxData.maxLeft);
      cropBoxData.top = Math.min(Math.max(cropBoxData.top, cropBoxData.minTop), cropBoxData.maxTop);
      cropBoxData.oldLeft = cropBoxData.left;
      cropBoxData.oldTop = cropBoxData.top;

      if (options.movable && options.cropBoxMovable) {
        // Turn to move the canvas when the crop box is equal to the container
        setData(this.face, DATA_ACTION, cropBoxData.width >= containerData.width && cropBoxData.height >= containerData.height ? ACTION_MOVE : ACTION_ALL);
      }

      setStyle(this.cropBox, assign({
        width: cropBoxData.width,
        height: cropBoxData.height
      }, getTransforms({
        translateX: cropBoxData.left,
        translateY: cropBoxData.top
      })));

      if (this.cropped && this.limited) {
        this.limitCanvas(true, true);
      }

      if (!this.disabled) {
        this.output();
      }
    },
    output: function output() {
      this.preview();
      dispatchEvent(this.element, EVENT_CROP, this.getData());
    }
  };

  var preview = {
    initPreview: function initPreview() {
      var crossOrigin = this.crossOrigin;
      var preview = this.options.preview;

      var url = crossOrigin ? this.crossOriginUrl : this.url;
      var image = document.createElement('img');

      if (crossOrigin) {
        image.crossOrigin = crossOrigin;
      }

      image.src = url;
      this.viewBox.appendChild(image);
      this.viewBoxImage = image;

      if (!preview) {
        return;
      }

      var previews = preview;

      if (typeof preview === 'string') {
        previews = this.element.ownerDocument.querySelectorAll(preview);
      } else if (preview.querySelector) {
        previews = [preview];
      }

      this.previews = previews;

      forEach(previews, function (el) {
        var img = document.createElement('img');

        // Save the original size for recover
        setData(el, DATA_PREVIEW, {
          width: el.offsetWidth,
          height: el.offsetHeight,
          html: el.innerHTML
        });

        if (crossOrigin) {
          img.crossOrigin = crossOrigin;
        }

        img.src = url;

        /**
         * Override img element styles
         * Add `display:block` to avoid margin top issue
         * Add `height:auto` to override `height` attribute on IE8
         * (Occur only when margin-top <= -height)
         */
        img.style.cssText = 'display:block;' + 'width:100%;' + 'height:auto;' + 'min-width:0!important;' + 'min-height:0!important;' + 'max-width:none!important;' + 'max-height:none!important;' + 'image-orientation:0deg!important;"';

        el.innerHTML = '';
        el.appendChild(img);
      });
    },
    resetPreview: function resetPreview() {
      forEach(this.previews, function (element) {
        var data = getData(element, DATA_PREVIEW);

        setStyle(element, {
          width: data.width,
          height: data.height
        });

        element.innerHTML = data.html;
        removeData(element, DATA_PREVIEW);
      });
    },
    preview: function preview() {
      var imageData = this.imageData,
          canvasData = this.canvasData,
          cropBoxData = this.cropBoxData;
      var cropBoxWidth = cropBoxData.width,
          cropBoxHeight = cropBoxData.height;
      var width = imageData.width,
          height = imageData.height;

      var left = cropBoxData.left - canvasData.left - imageData.left;
      var top = cropBoxData.top - canvasData.top - imageData.top;

      if (!this.cropped || this.disabled) {
        return;
      }

      setStyle(this.viewBoxImage, assign({
        width: width,
        height: height
      }, getTransforms(assign({
        translateX: -left,
        translateY: -top
      }, imageData))));

      forEach(this.previews, function (element) {
        var data = getData(element, DATA_PREVIEW);
        var originalWidth = data.width;
        var originalHeight = data.height;
        var newWidth = originalWidth;
        var newHeight = originalHeight;
        var ratio = 1;

        if (cropBoxWidth) {
          ratio = originalWidth / cropBoxWidth;
          newHeight = cropBoxHeight * ratio;
        }

        if (cropBoxHeight && newHeight > originalHeight) {
          ratio = originalHeight / cropBoxHeight;
          newWidth = cropBoxWidth * ratio;
          newHeight = originalHeight;
        }

        setStyle(element, {
          width: newWidth,
          height: newHeight
        });

        setStyle(element.getElementsByTagName('img')[0], assign({
          width: width * ratio,
          height: height * ratio
        }, getTransforms(assign({
          translateX: -left * ratio,
          translateY: -top * ratio
        }, imageData))));
      });
    }
  };

  var events = {
    bind: function bind() {
      var element = this.element,
          options = this.options,
          cropper = this.cropper;


      if (isFunction(options.cropstart)) {
        addListener(element, EVENT_CROP_START, options.cropstart);
      }

      if (isFunction(options.cropmove)) {
        addListener(element, EVENT_CROP_MOVE, options.cropmove);
      }

      if (isFunction(options.cropend)) {
        addListener(element, EVENT_CROP_END, options.cropend);
      }

      if (isFunction(options.crop)) {
        addListener(element, EVENT_CROP, options.crop);
      }

      if (isFunction(options.zoom)) {
        addListener(element, EVENT_ZOOM, options.zoom);
      }

      addListener(cropper, EVENT_POINTER_DOWN, this.onCropStart = this.cropStart.bind(this));

      if (options.zoomable && options.zoomOnWheel) {
        addListener(cropper, EVENT_WHEEL, this.onWheel = this.wheel.bind(this));
      }

      if (options.toggleDragModeOnDblclick) {
        addListener(cropper, EVENT_DBLCLICK, this.onDblclick = this.dblclick.bind(this));
      }

      addListener(element.ownerDocument, EVENT_POINTER_MOVE, this.onCropMove = this.cropMove.bind(this));
      addListener(element.ownerDocument, EVENT_POINTER_UP, this.onCropEnd = this.cropEnd.bind(this));

      if (options.responsive) {
        addListener(window, EVENT_RESIZE, this.onResize = this.resize.bind(this));
      }
    },
    unbind: function unbind() {
      var element = this.element,
          options = this.options,
          cropper = this.cropper;


      if (isFunction(options.cropstart)) {
        removeListener(element, EVENT_CROP_START, options.cropstart);
      }

      if (isFunction(options.cropmove)) {
        removeListener(element, EVENT_CROP_MOVE, options.cropmove);
      }

      if (isFunction(options.cropend)) {
        removeListener(element, EVENT_CROP_END, options.cropend);
      }

      if (isFunction(options.crop)) {
        removeListener(element, EVENT_CROP, options.crop);
      }

      if (isFunction(options.zoom)) {
        removeListener(element, EVENT_ZOOM, options.zoom);
      }

      removeListener(cropper, EVENT_POINTER_DOWN, this.onCropStart);

      if (options.zoomable && options.zoomOnWheel) {
        removeListener(cropper, EVENT_WHEEL, this.onWheel);
      }

      if (options.toggleDragModeOnDblclick) {
        removeListener(cropper, EVENT_DBLCLICK, this.onDblclick);
      }

      removeListener(element.ownerDocument, EVENT_POINTER_MOVE, this.onCropMove);
      removeListener(element.ownerDocument, EVENT_POINTER_UP, this.onCropEnd);

      if (options.responsive) {
        removeListener(window, EVENT_RESIZE, this.onResize);
      }
    }
  };

  var handlers = {
    resize: function resize() {
      var options = this.options,
          container = this.container,
          containerData = this.containerData;

      var minContainerWidth = Number(options.minContainerWidth) || 200;
      var minContainerHeight = Number(options.minContainerHeight) || 100;

      if (this.disabled || containerData.width <= minContainerWidth || containerData.height <= minContainerHeight) {
        return;
      }

      var ratio = container.offsetWidth / containerData.width;

      // Resize when width changed or height changed
      if (ratio !== 1 || container.offsetHeight !== containerData.height) {
        var canvasData = void 0;
        var cropBoxData = void 0;

        if (options.restore) {
          canvasData = this.getCanvasData();
          cropBoxData = this.getCropBoxData();
        }

        this.render();

        if (options.restore) {
          this.setCanvasData(forEach(canvasData, function (n, i) {
            canvasData[i] = n * ratio;
          }));
          this.setCropBoxData(forEach(cropBoxData, function (n, i) {
            cropBoxData[i] = n * ratio;
          }));
        }
      }
    },
    dblclick: function dblclick() {
      if (this.disabled || this.options.dragMode === DRAG_MODE_NONE) {
        return;
      }

      this.setDragMode(hasClass(this.dragBox, CLASS_CROP) ? DRAG_MODE_MOVE : DRAG_MODE_CROP);
    },
    wheel: function wheel(e) {
      var _this = this;

      var ratio = Number(this.options.wheelZoomRatio) || 0.1;
      var delta = 1;

      if (this.disabled) {
        return;
      }

      e.preventDefault();

      // Limit wheel speed to prevent zoom too fast (#21)
      if (this.wheeling) {
        return;
      }

      this.wheeling = true;

      setTimeout(function () {
        _this.wheeling = false;
      }, 50);

      if (e.deltaY) {
        delta = e.deltaY > 0 ? 1 : -1;
      } else if (e.wheelDelta) {
        delta = -e.wheelDelta / 120;
      } else if (e.detail) {
        delta = e.detail > 0 ? 1 : -1;
      }

      this.zoom(-delta * ratio, e);
    },
    cropStart: function cropStart(e) {
      if (this.disabled) {
        return;
      }

      var options = this.options,
          pointers = this.pointers;

      var action = void 0;

      if (e.changedTouches) {
        // Handle touch event
        forEach(e.changedTouches, function (touch) {
          pointers[touch.identifier] = getPointer(touch);
        });
      } else {
        // Handle mouse event and pointer event
        pointers[e.pointerId || 0] = getPointer(e);
      }

      if (Object.keys(pointers).length > 1 && options.zoomable && options.zoomOnTouch) {
        action = ACTION_ZOOM;
      } else {
        action = getData(e.target, DATA_ACTION);
      }

      if (!REGEXP_ACTIONS.test(action)) {
        return;
      }

      if (dispatchEvent(this.element, EVENT_CROP_START, {
        originalEvent: e,
        action: action
      }) === false) {
        return;
      }

      e.preventDefault();

      this.action = action;
      this.cropping = false;

      if (action === ACTION_CROP) {
        this.cropping = true;
        addClass(this.dragBox, CLASS_MODAL);
      }
    },
    cropMove: function cropMove(e) {
      var action = this.action;


      if (this.disabled || !action) {
        return;
      }

      var pointers = this.pointers;


      e.preventDefault();

      if (dispatchEvent(this.element, EVENT_CROP_MOVE, {
        originalEvent: e,
        action: action
      }) === false) {
        return;
      }

      if (e.changedTouches) {
        forEach(e.changedTouches, function (touch) {
          assign(pointers[touch.identifier], getPointer(touch, true));
        });
      } else {
        assign(pointers[e.pointerId || 0], getPointer(e, true));
      }

      this.change(e);
    },
    cropEnd: function cropEnd(e) {
      if (this.disabled) {
        return;
      }

      var action = this.action,
          pointers = this.pointers;


      if (e.changedTouches) {
        forEach(e.changedTouches, function (touch) {
          delete pointers[touch.identifier];
        });
      } else {
        delete pointers[e.pointerId || 0];
      }

      if (!action) {
        return;
      }

      e.preventDefault();

      if (!Object.keys(pointers).length) {
        this.action = '';
      }

      if (this.cropping) {
        this.cropping = false;
        toggleClass(this.dragBox, CLASS_MODAL, this.cropped && this.options.modal);
      }

      dispatchEvent(this.element, EVENT_CROP_END, {
        originalEvent: e,
        action: action
      });
    }
  };

  var change = {
    change: function change(e) {
      var options = this.options,
          canvasData = this.canvasData,
          containerData = this.containerData,
          cropBoxData = this.cropBoxData,
          pointers = this.pointers;
      var action = this.action;
      var aspectRatio = options.aspectRatio;
      var left = cropBoxData.left,
          top = cropBoxData.top,
          width = cropBoxData.width,
          height = cropBoxData.height;

      var right = left + width;
      var bottom = top + height;
      var minLeft = 0;
      var minTop = 0;
      var maxWidth = containerData.width;
      var maxHeight = containerData.height;
      var renderable = true;
      var offset = void 0;

      // Locking aspect ratio in "free mode" by holding shift key
      if (!aspectRatio && e.shiftKey) {
        aspectRatio = width && height ? width / height : 1;
      }

      if (this.limited) {
        minLeft = cropBoxData.minLeft;
        minTop = cropBoxData.minTop;

        maxWidth = minLeft + Math.min(containerData.width, canvasData.width, canvasData.left + canvasData.width);
        maxHeight = minTop + Math.min(containerData.height, canvasData.height, canvasData.top + canvasData.height);
      }

      var pointer = pointers[Object.keys(pointers)[0]];
      var range = {
        x: pointer.endX - pointer.startX,
        y: pointer.endY - pointer.startY
      };
      var check = function check(side) {
        switch (side) {
          case ACTION_EAST:
            if (right + range.x > maxWidth) {
              range.x = maxWidth - right;
            }

            break;

          case ACTION_WEST:
            if (left + range.x < minLeft) {
              range.x = minLeft - left;
            }

            break;

          case ACTION_NORTH:
            if (top + range.y < minTop) {
              range.y = minTop - top;
            }

            break;

          case ACTION_SOUTH:
            if (bottom + range.y > maxHeight) {
              range.y = maxHeight - bottom;
            }

            break;

          default:
        }
      };

      switch (action) {
        // Move crop box
        case ACTION_ALL:
          left += range.x;
          top += range.y;
          break;

        // Resize crop box
        case ACTION_EAST:
          if (range.x >= 0 && (right >= maxWidth || aspectRatio && (top <= minTop || bottom >= maxHeight))) {
            renderable = false;
            break;
          }

          check(ACTION_EAST);
          width += range.x;

          if (aspectRatio) {
            height = width / aspectRatio;
            top -= range.x / aspectRatio / 2;
          }

          if (width < 0) {
            action = ACTION_WEST;
            width = 0;
          }

          break;

        case ACTION_NORTH:
          if (range.y <= 0 && (top <= minTop || aspectRatio && (left <= minLeft || right >= maxWidth))) {
            renderable = false;
            break;
          }

          check(ACTION_NORTH);
          height -= range.y;
          top += range.y;

          if (aspectRatio) {
            width = height * aspectRatio;
            left += range.y * aspectRatio / 2;
          }

          if (height < 0) {
            action = ACTION_SOUTH;
            height = 0;
          }

          break;

        case ACTION_WEST:
          if (range.x <= 0 && (left <= minLeft || aspectRatio && (top <= minTop || bottom >= maxHeight))) {
            renderable = false;
            break;
          }

          check(ACTION_WEST);
          width -= range.x;
          left += range.x;

          if (aspectRatio) {
            height = width / aspectRatio;
            top += range.x / aspectRatio / 2;
          }

          if (width < 0) {
            action = ACTION_EAST;
            width = 0;
          }

          break;

        case ACTION_SOUTH:
          if (range.y >= 0 && (bottom >= maxHeight || aspectRatio && (left <= minLeft || right >= maxWidth))) {
            renderable = false;
            break;
          }

          check(ACTION_SOUTH);
          height += range.y;

          if (aspectRatio) {
            width = height * aspectRatio;
            left -= range.y * aspectRatio / 2;
          }

          if (height < 0) {
            action = ACTION_NORTH;
            height = 0;
          }

          break;

        case ACTION_NORTH_EAST:
          if (aspectRatio) {
            if (range.y <= 0 && (top <= minTop || right >= maxWidth)) {
              renderable = false;
              break;
            }

            check(ACTION_NORTH);
            height -= range.y;
            top += range.y;
            width = height * aspectRatio;
          } else {
            check(ACTION_NORTH);
            check(ACTION_EAST);

            if (range.x >= 0) {
              if (right < maxWidth) {
                width += range.x;
              } else if (range.y <= 0 && top <= minTop) {
                renderable = false;
              }
            } else {
              width += range.x;
            }

            if (range.y <= 0) {
              if (top > minTop) {
                height -= range.y;
                top += range.y;
              }
            } else {
              height -= range.y;
              top += range.y;
            }
          }

          if (width < 0 && height < 0) {
            action = ACTION_SOUTH_WEST;
            height = 0;
            width = 0;
          } else if (width < 0) {
            action = ACTION_NORTH_WEST;
            width = 0;
          } else if (height < 0) {
            action = ACTION_SOUTH_EAST;
            height = 0;
          }

          break;

        case ACTION_NORTH_WEST:
          if (aspectRatio) {
            if (range.y <= 0 && (top <= minTop || left <= minLeft)) {
              renderable = false;
              break;
            }

            check(ACTION_NORTH);
            height -= range.y;
            top += range.y;
            width = height * aspectRatio;
            left += range.y * aspectRatio;
          } else {
            check(ACTION_NORTH);
            check(ACTION_WEST);

            if (range.x <= 0) {
              if (left > minLeft) {
                width -= range.x;
                left += range.x;
              } else if (range.y <= 0 && top <= minTop) {
                renderable = false;
              }
            } else {
              width -= range.x;
              left += range.x;
            }

            if (range.y <= 0) {
              if (top > minTop) {
                height -= range.y;
                top += range.y;
              }
            } else {
              height -= range.y;
              top += range.y;
            }
          }

          if (width < 0 && height < 0) {
            action = ACTION_SOUTH_EAST;
            height = 0;
            width = 0;
          } else if (width < 0) {
            action = ACTION_NORTH_EAST;
            width = 0;
          } else if (height < 0) {
            action = ACTION_SOUTH_WEST;
            height = 0;
          }

          break;

        case ACTION_SOUTH_WEST:
          if (aspectRatio) {
            if (range.x <= 0 && (left <= minLeft || bottom >= maxHeight)) {
              renderable = false;
              break;
            }

            check(ACTION_WEST);
            width -= range.x;
            left += range.x;
            height = width / aspectRatio;
          } else {
            check(ACTION_SOUTH);
            check(ACTION_WEST);

            if (range.x <= 0) {
              if (left > minLeft) {
                width -= range.x;
                left += range.x;
              } else if (range.y >= 0 && bottom >= maxHeight) {
                renderable = false;
              }
            } else {
              width -= range.x;
              left += range.x;
            }

            if (range.y >= 0) {
              if (bottom < maxHeight) {
                height += range.y;
              }
            } else {
              height += range.y;
            }
          }

          if (width < 0 && height < 0) {
            action = ACTION_NORTH_EAST;
            height = 0;
            width = 0;
          } else if (width < 0) {
            action = ACTION_SOUTH_EAST;
            width = 0;
          } else if (height < 0) {
            action = ACTION_NORTH_WEST;
            height = 0;
          }

          break;

        case ACTION_SOUTH_EAST:
          if (aspectRatio) {
            if (range.x >= 0 && (right >= maxWidth || bottom >= maxHeight)) {
              renderable = false;
              break;
            }

            check(ACTION_EAST);
            width += range.x;
            height = width / aspectRatio;
          } else {
            check(ACTION_SOUTH);
            check(ACTION_EAST);

            if (range.x >= 0) {
              if (right < maxWidth) {
                width += range.x;
              } else if (range.y >= 0 && bottom >= maxHeight) {
                renderable = false;
              }
            } else {
              width += range.x;
            }

            if (range.y >= 0) {
              if (bottom < maxHeight) {
                height += range.y;
              }
            } else {
              height += range.y;
            }
          }

          if (width < 0 && height < 0) {
            action = ACTION_NORTH_WEST;
            height = 0;
            width = 0;
          } else if (width < 0) {
            action = ACTION_SOUTH_WEST;
            width = 0;
          } else if (height < 0) {
            action = ACTION_NORTH_EAST;
            height = 0;
          }

          break;

        // Move canvas
        case ACTION_MOVE:
          this.move(range.x, range.y);
          renderable = false;
          break;

        // Zoom canvas
        case ACTION_ZOOM:
          this.zoom(getMaxZoomRatio(pointers), e);
          renderable = false;
          break;

        // Create crop box
        case ACTION_CROP:
          if (!range.x || !range.y) {
            renderable = false;
            break;
          }

          offset = getOffset(this.cropper);
          left = pointer.startX - offset.left;
          top = pointer.startY - offset.top;
          width = cropBoxData.minWidth;
          height = cropBoxData.minHeight;

          if (range.x > 0) {
            action = range.y > 0 ? ACTION_SOUTH_EAST : ACTION_NORTH_EAST;
          } else if (range.x < 0) {
            left -= width;
            action = range.y > 0 ? ACTION_SOUTH_WEST : ACTION_NORTH_WEST;
          }

          if (range.y < 0) {
            top -= height;
          }

          // Show the crop box if is hidden
          if (!this.cropped) {
            removeClass(this.cropBox, CLASS_HIDDEN);
            this.cropped = true;

            if (this.limited) {
              this.limitCropBox(true, true);
            }
          }

          break;

        default:
      }

      if (renderable) {
        cropBoxData.width = width;
        cropBoxData.height = height;
        cropBoxData.left = left;
        cropBoxData.top = top;
        this.action = action;
        this.renderCropBox();
      }

      // Override
      forEach(pointers, function (p) {
        p.startX = p.endX;
        p.startY = p.endY;
      });
    }
  };

  var methods = {
    // Show the crop box manually
    crop: function crop() {
      if (this.ready && !this.cropped && !this.disabled) {
        this.cropped = true;
        this.limitCropBox(true, true);

        if (this.options.modal) {
          addClass(this.dragBox, CLASS_MODAL);
        }

        removeClass(this.cropBox, CLASS_HIDDEN);
        this.setCropBoxData(this.initialCropBoxData);
      }

      return this;
    },


    // Reset the image and crop box to their initial states
    reset: function reset() {
      if (this.ready && !this.disabled) {
        this.imageData = assign({}, this.initialImageData);
        this.canvasData = assign({}, this.initialCanvasData);
        this.cropBoxData = assign({}, this.initialCropBoxData);
        this.renderCanvas();

        if (this.cropped) {
          this.renderCropBox();
        }
      }

      return this;
    },


    // Clear the crop box
    clear: function clear() {
      if (this.cropped && !this.disabled) {
        assign(this.cropBoxData, {
          left: 0,
          top: 0,
          width: 0,
          height: 0
        });

        this.cropped = false;
        this.renderCropBox();
        this.limitCanvas(true, true);

        // Render canvas after crop box rendered
        this.renderCanvas();
        removeClass(this.dragBox, CLASS_MODAL);
        addClass(this.cropBox, CLASS_HIDDEN);
      }

      return this;
    },


    /**
     * Replace the image's src and rebuild the cropper
     * @param {string} url - The new URL.
     * @param {boolean} [hasSameSize] - Indicate if the new image has the same size as the old one.
     * @returns {Cropper} this
     */
    replace: function replace(url) {
      var hasSameSize = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

      if (!this.disabled && url) {
        if (this.isImg) {
          this.element.src = url;
        }

        if (hasSameSize) {
          this.url = url;
          this.image.src = url;

          if (this.ready) {
            this.viewBoxImage.src = url;

            forEach(this.previews, function (element) {
              element.getElementsByTagName('img')[0].src = url;
            });
          }
        } else {
          if (this.isImg) {
            this.replaced = true;
          }

          this.options.data = null;
          this.uncreate();
          this.load(url);
        }
      }

      return this;
    },


    // Enable (unfreeze) the cropper
    enable: function enable() {
      if (this.ready && this.disabled) {
        this.disabled = false;
        removeClass(this.cropper, CLASS_DISABLED);
      }

      return this;
    },


    // Disable (freeze) the cropper
    disable: function disable() {
      if (this.ready && !this.disabled) {
        this.disabled = true;
        addClass(this.cropper, CLASS_DISABLED);
      }

      return this;
    },


    /**
     * Destroy the cropper and remove the instance from the image
     * @returns {Cropper} this
     */
    destroy: function destroy() {
      var element = this.element;


      if (!getData(element, NAMESPACE)) {
        return this;
      }

      if (this.isImg && this.replaced) {
        element.src = this.originalUrl;
      }

      this.uncreate();
      removeData(element, NAMESPACE);

      return this;
    },


    /**
     * Move the canvas with relative offsets
     * @param {number} offsetX - The relative offset distance on the x-axis.
     * @param {number} [offsetY=offsetX] - The relative offset distance on the y-axis.
     * @returns {Cropper} this
     */
    move: function move(offsetX) {
      var offsetY = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : offsetX;
      var _canvasData = this.canvasData,
          left = _canvasData.left,
          top = _canvasData.top;


      return this.moveTo(isUndefined(offsetX) ? offsetX : left + Number(offsetX), isUndefined(offsetY) ? offsetY : top + Number(offsetY));
    },


    /**
     * Move the canvas to an absolute point
     * @param {number} x - The x-axis coordinate.
     * @param {number} [y=x] - The y-axis coordinate.
     * @returns {Cropper} this
     */
    moveTo: function moveTo(x) {
      var y = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : x;
      var canvasData = this.canvasData;

      var changed = false;

      x = Number(x);
      y = Number(y);

      if (this.ready && !this.disabled && this.options.movable) {
        if (isNumber(x)) {
          canvasData.left = x;
          changed = true;
        }

        if (isNumber(y)) {
          canvasData.top = y;
          changed = true;
        }

        if (changed) {
          this.renderCanvas(true);
        }
      }

      return this;
    },


    /**
     * Zoom the canvas with a relative ratio
     * @param {number} ratio - The target ratio.
     * @param {Event} _originalEvent - The original event if any.
     * @returns {Cropper} this
     */
    zoom: function zoom(ratio, _originalEvent) {
      var canvasData = this.canvasData;


      ratio = Number(ratio);

      if (ratio < 0) {
        ratio = 1 / (1 - ratio);
      } else {
        ratio = 1 + ratio;
      }

      return this.zoomTo(canvasData.width * ratio / canvasData.naturalWidth, null, _originalEvent);
    },


    /**
     * Zoom the canvas to an absolute ratio
     * @param {number} ratio - The target ratio.
     * @param {Object} pivot - The zoom pivot point coordinate.
     * @param {Event} _originalEvent - The original event if any.
     * @returns {Cropper} this
     */
    zoomTo: function zoomTo(ratio, pivot, _originalEvent) {
      var options = this.options,
          canvasData = this.canvasData;
      var width = canvasData.width,
          height = canvasData.height,
          naturalWidth = canvasData.naturalWidth,
          naturalHeight = canvasData.naturalHeight;


      ratio = Number(ratio);

      if (ratio >= 0 && this.ready && !this.disabled && options.zoomable) {
        var newWidth = naturalWidth * ratio;
        var newHeight = naturalHeight * ratio;

        if (dispatchEvent(this.element, EVENT_ZOOM, {
          originalEvent: _originalEvent,
          oldRatio: width / naturalWidth,
          ratio: newWidth / naturalWidth
        }) === false) {
          return this;
        }

        if (_originalEvent) {
          var pointers = this.pointers;

          var offset = getOffset(this.cropper);
          var center = pointers && Object.keys(pointers).length ? getPointersCenter(pointers) : {
            pageX: _originalEvent.pageX,
            pageY: _originalEvent.pageY
          };

          // Zoom from the triggering point of the event
          canvasData.left -= (newWidth - width) * ((center.pageX - offset.left - canvasData.left) / width);
          canvasData.top -= (newHeight - height) * ((center.pageY - offset.top - canvasData.top) / height);
        } else if (isPlainObject(pivot) && isNumber(pivot.x) && isNumber(pivot.y)) {
          canvasData.left -= (newWidth - width) * ((pivot.x - canvasData.left) / width);
          canvasData.top -= (newHeight - height) * ((pivot.y - canvasData.top) / height);
        } else {
          // Zoom from the center of the canvas
          canvasData.left -= (newWidth - width) / 2;
          canvasData.top -= (newHeight - height) / 2;
        }

        canvasData.width = newWidth;
        canvasData.height = newHeight;
        this.renderCanvas(true);
      }

      return this;
    },


    /**
     * Rotate the canvas with a relative degree
     * @param {number} degree - The rotate degree.
     * @returns {Cropper} this
     */
    rotate: function rotate(degree) {
      return this.rotateTo((this.imageData.rotate || 0) + Number(degree));
    },


    /**
     * Rotate the canvas to an absolute degree
     * @param {number} degree - The rotate degree.
     * @returns {Cropper} this
     */
    rotateTo: function rotateTo(degree) {
      degree = Number(degree);

      if (isNumber(degree) && this.ready && !this.disabled && this.options.rotatable) {
        this.imageData.rotate = degree % 360;
        this.renderCanvas(true, true);
      }

      return this;
    },


    /**
     * Scale the image on the x-axis.
     * @param {number} scaleX - The scale ratio on the x-axis.
     * @returns {Cropper} this
     */
    scaleX: function scaleX(_scaleX) {
      var scaleY = this.imageData.scaleY;


      return this.scale(_scaleX, isNumber(scaleY) ? scaleY : 1);
    },


    /**
     * Scale the image on the y-axis.
     * @param {number} scaleY - The scale ratio on the y-axis.
     * @returns {Cropper} this
     */
    scaleY: function scaleY(_scaleY) {
      var scaleX = this.imageData.scaleX;


      return this.scale(isNumber(scaleX) ? scaleX : 1, _scaleY);
    },


    /**
     * Scale the image
     * @param {number} scaleX - The scale ratio on the x-axis.
     * @param {number} [scaleY=scaleX] - The scale ratio on the y-axis.
     * @returns {Cropper} this
     */
    scale: function scale(scaleX) {
      var scaleY = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : scaleX;
      var imageData = this.imageData;

      var transformed = false;

      scaleX = Number(scaleX);
      scaleY = Number(scaleY);

      if (this.ready && !this.disabled && this.options.scalable) {
        if (isNumber(scaleX)) {
          imageData.scaleX = scaleX;
          transformed = true;
        }

        if (isNumber(scaleY)) {
          imageData.scaleY = scaleY;
          transformed = true;
        }

        if (transformed) {
          this.renderCanvas(true, true);
        }
      }

      return this;
    },


    /**
     * Get the cropped area position and size data (base on the original image)
     * @param {boolean} [rounded=false] - Indicate if round the data values or not.
     * @returns {Object} The result cropped data.
     */
    getData: function getData$$1() {
      var rounded = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      var options = this.options,
          imageData = this.imageData,
          canvasData = this.canvasData,
          cropBoxData = this.cropBoxData;

      var data = void 0;

      if (this.ready && this.cropped) {
        data = {
          x: cropBoxData.left - canvasData.left,
          y: cropBoxData.top - canvasData.top,
          width: cropBoxData.width,
          height: cropBoxData.height
        };

        var ratio = imageData.width / imageData.naturalWidth;

        forEach(data, function (n, i) {
          n /= ratio;
          data[i] = rounded ? Math.round(n) : n;
        });
      } else {
        data = {
          x: 0,
          y: 0,
          width: 0,
          height: 0
        };
      }

      if (options.rotatable) {
        data.rotate = imageData.rotate || 0;
      }

      if (options.scalable) {
        data.scaleX = imageData.scaleX || 1;
        data.scaleY = imageData.scaleY || 1;
      }

      return data;
    },


    /**
     * Set the cropped area position and size with new data
     * @param {Object} data - The new data.
     * @returns {Cropper} this
     */
    setData: function setData$$1(data) {
      var options = this.options,
          imageData = this.imageData,
          canvasData = this.canvasData;

      var cropBoxData = {};

      if (this.ready && !this.disabled && isPlainObject(data)) {
        var transformed = false;

        if (options.rotatable) {
          if (isNumber(data.rotate) && data.rotate !== imageData.rotate) {
            imageData.rotate = data.rotate;
            transformed = true;
          }
        }

        if (options.scalable) {
          if (isNumber(data.scaleX) && data.scaleX !== imageData.scaleX) {
            imageData.scaleX = data.scaleX;
            transformed = true;
          }

          if (isNumber(data.scaleY) && data.scaleY !== imageData.scaleY) {
            imageData.scaleY = data.scaleY;
            transformed = true;
          }
        }

        if (transformed) {
          this.renderCanvas(true, true);
        }

        var ratio = imageData.width / imageData.naturalWidth;

        if (isNumber(data.x)) {
          cropBoxData.left = data.x * ratio + canvasData.left;
        }

        if (isNumber(data.y)) {
          cropBoxData.top = data.y * ratio + canvasData.top;
        }

        if (isNumber(data.width)) {
          cropBoxData.width = data.width * ratio;
        }

        if (isNumber(data.height)) {
          cropBoxData.height = data.height * ratio;
        }

        this.setCropBoxData(cropBoxData);
      }

      return this;
    },


    /**
     * Get the container size data.
     * @returns {Object} The result container data.
     */
    getContainerData: function getContainerData() {
      return this.ready ? assign({}, this.containerData) : {};
    },


    /**
     * Get the image position and size data.
     * @returns {Object} The result image data.
     */
    getImageData: function getImageData() {
      return this.sized ? assign({}, this.imageData) : {};
    },


    /**
     * Get the canvas position and size data.
     * @returns {Object} The result canvas data.
     */
    getCanvasData: function getCanvasData() {
      var canvasData = this.canvasData;

      var data = {};

      if (this.ready) {
        forEach(['left', 'top', 'width', 'height', 'naturalWidth', 'naturalHeight'], function (n) {
          data[n] = canvasData[n];
        });
      }

      return data;
    },


    /**
     * Set the canvas position and size with new data.
     * @param {Object} data - The new canvas data.
     * @returns {Cropper} this
     */
    setCanvasData: function setCanvasData(data) {
      var canvasData = this.canvasData;
      var aspectRatio = canvasData.aspectRatio;


      if (this.ready && !this.disabled && isPlainObject(data)) {
        if (isNumber(data.left)) {
          canvasData.left = data.left;
        }

        if (isNumber(data.top)) {
          canvasData.top = data.top;
        }

        if (isNumber(data.width)) {
          canvasData.width = data.width;
          canvasData.height = data.width / aspectRatio;
        } else if (isNumber(data.height)) {
          canvasData.height = data.height;
          canvasData.width = data.height * aspectRatio;
        }

        this.renderCanvas(true);
      }

      return this;
    },


    /**
     * Get the crop box position and size data.
     * @returns {Object} The result crop box data.
     */
    getCropBoxData: function getCropBoxData() {
      var cropBoxData = this.cropBoxData;

      var data = void 0;

      if (this.ready && this.cropped) {
        data = {
          left: cropBoxData.left,
          top: cropBoxData.top,
          width: cropBoxData.width,
          height: cropBoxData.height
        };
      }

      return data || {};
    },


    /**
     * Set the crop box position and size with new data.
     * @param {Object} data - The new crop box data.
     * @returns {Cropper} this
     */
    setCropBoxData: function setCropBoxData(data) {
      var cropBoxData = this.cropBoxData;
      var aspectRatio = this.options.aspectRatio;

      var widthChanged = void 0;
      var heightChanged = void 0;

      if (this.ready && this.cropped && !this.disabled && isPlainObject(data)) {
        if (isNumber(data.left)) {
          cropBoxData.left = data.left;
        }

        if (isNumber(data.top)) {
          cropBoxData.top = data.top;
        }

        if (isNumber(data.width) && data.width !== cropBoxData.width) {
          widthChanged = true;
          cropBoxData.width = data.width;
        }

        if (isNumber(data.height) && data.height !== cropBoxData.height) {
          heightChanged = true;
          cropBoxData.height = data.height;
        }

        if (aspectRatio) {
          if (widthChanged) {
            cropBoxData.height = cropBoxData.width / aspectRatio;
          } else if (heightChanged) {
            cropBoxData.width = cropBoxData.height * aspectRatio;
          }
        }

        this.renderCropBox();
      }

      return this;
    },


    /**
     * Get a canvas drawn the cropped image.
     * @param {Object} [options={}] - The config options.
     * @returns {HTMLCanvasElement} - The result canvas.
     */
    getCroppedCanvas: function getCroppedCanvas() {
      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

      if (!this.ready || !window.HTMLCanvasElement) {
        return null;
      }

      var canvasData = this.canvasData;

      var source = getSourceCanvas(this.image, this.imageData, canvasData, options);

      // Returns the source canvas if it is not cropped.
      if (!this.cropped) {
        return source;
      }

      var _getData = this.getData(),
          initialX = _getData.x,
          initialY = _getData.y,
          initialWidth = _getData.width,
          initialHeight = _getData.height;

      var ratio = source.width / Math.floor(canvasData.naturalWidth);

      if (ratio !== 1) {
        initialX *= ratio;
        initialY *= ratio;
        initialWidth *= ratio;
        initialHeight *= ratio;
      }

      var aspectRatio = initialWidth / initialHeight;
      var maxSizes = getAdjustedSizes({
        aspectRatio: aspectRatio,
        width: options.maxWidth || Infinity,
        height: options.maxHeight || Infinity
      });
      var minSizes = getAdjustedSizes({
        aspectRatio: aspectRatio,
        width: options.minWidth || 0,
        height: options.minHeight || 0
      }, 'cover');

      var _getAdjustedSizes = getAdjustedSizes({
        aspectRatio: aspectRatio,
        width: options.width || (ratio !== 1 ? source.width : initialWidth),
        height: options.height || (ratio !== 1 ? source.height : initialHeight)
      }),
          width = _getAdjustedSizes.width,
          height = _getAdjustedSizes.height;

      width = Math.min(maxSizes.width, Math.max(minSizes.width, width));
      height = Math.min(maxSizes.height, Math.max(minSizes.height, height));

      var canvas = document.createElement('canvas');
      var context = canvas.getContext('2d');

      canvas.width = normalizeDecimalNumber(width);
      canvas.height = normalizeDecimalNumber(height);

      context.fillStyle = options.fillColor || 'transparent';
      context.fillRect(0, 0, width, height);

      var _options$imageSmoothi = options.imageSmoothingEnabled,
          imageSmoothingEnabled = _options$imageSmoothi === undefined ? true : _options$imageSmoothi,
          imageSmoothingQuality = options.imageSmoothingQuality;


      context.imageSmoothingEnabled = imageSmoothingEnabled;

      if (imageSmoothingQuality) {
        context.imageSmoothingQuality = imageSmoothingQuality;
      }

      // https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D.drawImage
      var sourceWidth = source.width;
      var sourceHeight = source.height;

      // Source canvas parameters
      var srcX = initialX;
      var srcY = initialY;
      var srcWidth = void 0;
      var srcHeight = void 0;

      // Destination canvas parameters
      var dstX = void 0;
      var dstY = void 0;
      var dstWidth = void 0;
      var dstHeight = void 0;

      if (srcX <= -initialWidth || srcX > sourceWidth) {
        srcX = 0;
        srcWidth = 0;
        dstX = 0;
        dstWidth = 0;
      } else if (srcX <= 0) {
        dstX = -srcX;
        srcX = 0;
        srcWidth = Math.min(sourceWidth, initialWidth + srcX);
        dstWidth = srcWidth;
      } else if (srcX <= sourceWidth) {
        dstX = 0;
        srcWidth = Math.min(initialWidth, sourceWidth - srcX);
        dstWidth = srcWidth;
      }

      if (srcWidth <= 0 || srcY <= -initialHeight || srcY > sourceHeight) {
        srcY = 0;
        srcHeight = 0;
        dstY = 0;
        dstHeight = 0;
      } else if (srcY <= 0) {
        dstY = -srcY;
        srcY = 0;
        srcHeight = Math.min(sourceHeight, initialHeight + srcY);
        dstHeight = srcHeight;
      } else if (srcY <= sourceHeight) {
        dstY = 0;
        srcHeight = Math.min(initialHeight, sourceHeight - srcY);
        dstHeight = srcHeight;
      }

      var params = [srcX, srcY, srcWidth, srcHeight];

      // Avoid "IndexSizeError"
      if (dstWidth > 0 && dstHeight > 0) {
        var scale = width / initialWidth;

        params.push(dstX * scale, dstY * scale, dstWidth * scale, dstHeight * scale);
      }

      // All the numerical parameters should be integer for `drawImage`
      // https://github.com/fengyuanchen/cropper/issues/476
      context.drawImage.apply(context, [source].concat(toConsumableArray(params.map(function (param) {
        return Math.floor(normalizeDecimalNumber(param));
      }))));

      return canvas;
    },


    /**
     * Change the aspect ratio of the crop box.
     * @param {number} aspectRatio - The new aspect ratio.
     * @returns {Cropper} this
     */
    setAspectRatio: function setAspectRatio(aspectRatio) {
      var options = this.options;


      if (!this.disabled && !isUndefined(aspectRatio)) {
        // 0 -> NaN
        options.aspectRatio = Math.max(0, aspectRatio) || NaN;

        if (this.ready) {
          this.initCropBox();

          if (this.cropped) {
            this.renderCropBox();
          }
        }
      }

      return this;
    },


    /**
     * Change the drag mode.
     * @param {string} mode - The new drag mode.
     * @returns {Cropper} this
     */
    setDragMode: function setDragMode(mode) {
      var options = this.options,
          dragBox = this.dragBox,
          face = this.face;


      if (this.ready && !this.disabled) {
        var croppable = mode === DRAG_MODE_CROP;
        var movable = options.movable && mode === DRAG_MODE_MOVE;

        mode = croppable || movable ? mode : DRAG_MODE_NONE;

        options.dragMode = mode;
        setData(dragBox, DATA_ACTION, mode);
        toggleClass(dragBox, CLASS_CROP, croppable);
        toggleClass(dragBox, CLASS_MOVE, movable);

        if (!options.cropBoxMovable) {
          // Sync drag mode to crop box when it is not movable
          setData(face, DATA_ACTION, mode);
          toggleClass(face, CLASS_CROP, croppable);
          toggleClass(face, CLASS_MOVE, movable);
        }
      }

      return this;
    }
  };

  var AnotherCropper = WINDOW.Cropper;

  var Cropper = function () {
    /**
     * Create a new Cropper.
     * @param {Element} element - The target element for cropping.
     * @param {Object} [options={}] - The configuration options.
     */
    function Cropper(element) {
      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      classCallCheck(this, Cropper);

      if (!element || !REGEXP_TAG_NAME.test(element.tagName)) {
        throw new Error('The first argument is required and must be an <img> or <canvas> element.');
      }

      this.element = element;
      this.options = assign({}, DEFAULTS, isPlainObject(options) && options);
      this.cropped = false;
      this.disabled = false;
      this.pointers = {};
      this.ready = false;
      this.reloading = false;
      this.replaced = false;
      this.sized = false;
      this.sizing = false;
      this.init();
    }

    createClass(Cropper, [{
      key: 'init',
      value: function init() {
        var element = this.element;

        var tagName = element.tagName.toLowerCase();
        var url = void 0;

        if (getData(element, NAMESPACE)) {
          return;
        }

        setData(element, NAMESPACE, this);

        if (tagName === 'img') {
          this.isImg = true;

          // e.g.: "img/picture.jpg"
          url = element.getAttribute('src') || '';
          this.originalUrl = url;

          // Stop when it's a blank image
          if (!url) {
            return;
          }

          // e.g.: "http://example.com/img/picture.jpg"
          url = element.src;
        } else if (tagName === 'canvas' && window.HTMLCanvasElement) {
          url = element.toDataURL();
        }

        this.load(url);
      }
    }, {
      key: 'load',
      value: function load(url) {
        var _this = this;

        if (!url) {
          return;
        }

        this.url = url;
        this.imageData = {};

        var element = this.element,
            options = this.options;


        if (!options.checkOrientation || !window.ArrayBuffer) {
          this.clone();
          return;
        }

        // XMLHttpRequest disallows to open a Data URL in some browsers like IE11 and Safari
        if (REGEXP_DATA_URL.test(url)) {
          if (REGEXP_DATA_URL_JPEG.test(url)) {
            this.read(dataURLToArrayBuffer(url));
          } else {
            this.clone();
          }

          return;
        }

        var xhr = new XMLHttpRequest();

        this.reloading = true;
        this.xhr = xhr;

        var done = function done() {
          _this.reloading = false;
          _this.xhr = null;
        };

        xhr.ontimeout = done;
        xhr.onabort = done;
        xhr.onerror = function () {
          done();
          _this.clone();
        };

        xhr.onload = function () {
          done();
          _this.read(xhr.response);
        };

        // Bust cache when there is a "crossOrigin" property
        if (options.checkCrossOrigin && isCrossOriginURL(url) && element.crossOrigin) {
          url = addTimestamp(url);
        }

        xhr.open('get', url);
        xhr.responseType = 'arraybuffer';
        xhr.withCredentials = element.crossOrigin === 'use-credentials';
        xhr.send();
      }
    }, {
      key: 'read',
      value: function read(arrayBuffer) {
        var options = this.options,
            imageData = this.imageData;

        var orientation = getOrientation(arrayBuffer);
        var rotate = 0;
        var scaleX = 1;
        var scaleY = 1;

        if (orientation > 1) {
          this.url = arrayBufferToDataURL(arrayBuffer, 'image/jpeg');

          var _parseOrientation = parseOrientation(orientation);

          rotate = _parseOrientation.rotate;
          scaleX = _parseOrientation.scaleX;
          scaleY = _parseOrientation.scaleY;
        }

        if (options.rotatable) {
          imageData.rotate = rotate;
        }

        if (options.scalable) {
          imageData.scaleX = scaleX;
          imageData.scaleY = scaleY;
        }

        this.clone();
      }
    }, {
      key: 'clone',
      value: function clone() {
        var element = this.element,
            url = this.url;

        var crossOrigin = void 0;
        var crossOriginUrl = void 0;

        if (this.options.checkCrossOrigin && isCrossOriginURL(url)) {
          crossOrigin = element.crossOrigin;


          if (crossOrigin) {
            crossOriginUrl = url;
          } else {
            crossOrigin = 'anonymous';

            // Bust cache when there is not a "crossOrigin" property
            crossOriginUrl = addTimestamp(url);
          }
        }

        this.crossOrigin = crossOrigin;
        this.crossOriginUrl = crossOriginUrl;

        var image = document.createElement('img');

        if (crossOrigin) {
          image.crossOrigin = crossOrigin;
        }

        image.src = crossOriginUrl || url;

        var start = this.start.bind(this);
        var stop = this.stop.bind(this);

        this.image = image;
        this.onStart = start;
        this.onStop = stop;

        if (this.isImg) {
          if (element.complete) {
            // start asynchronously to keep `this.cropper` is accessible in `ready` event handler.
            this.timeout = setTimeout(start, 0);
          } else {
            addListener(element, EVENT_LOAD, start, {
              once: true
            });
          }
        } else {
          image.onload = start;
          image.onerror = stop;
          addClass(image, CLASS_HIDE);
          element.parentNode.insertBefore(image, element.nextSibling);
        }
      }
    }, {
      key: 'start',
      value: function start(event) {
        var _this2 = this;

        var image = this.isImg ? this.element : this.image;

        if (event) {
          image.onload = null;
          image.onerror = null;
        }

        this.sizing = true;

        var IS_SAFARI = WINDOW.navigator && /(Macintosh|iPhone|iPod|iPad).*AppleWebKit/i.test(WINDOW.navigator.userAgent);
        var done = function done(naturalWidth, naturalHeight) {
          assign(_this2.imageData, {
            naturalWidth: naturalWidth,
            naturalHeight: naturalHeight,
            aspectRatio: naturalWidth / naturalHeight
          });
          _this2.sizing = false;
          _this2.sized = true;
          _this2.build();
        };

        // Modern browsers (except Safari)
        if (image.naturalWidth && !IS_SAFARI) {
          done(image.naturalWidth, image.naturalHeight);
          return;
        }

        var sizingImage = document.createElement('img');
        var body = document.body || document.documentElement;

        this.sizingImage = sizingImage;

        sizingImage.onload = function () {
          done(sizingImage.width, sizingImage.height);

          if (!IS_SAFARI) {
            body.removeChild(sizingImage);
          }
        };

        sizingImage.src = image.src;

        // iOS Safari will convert the image automatically
        // with its orientation once append it into DOM (#279)
        if (!IS_SAFARI) {
          sizingImage.style.cssText = 'left:0;' + 'max-height:none!important;' + 'max-width:none!important;' + 'min-height:0!important;' + 'min-width:0!important;' + 'opacity:0;' + 'position:absolute;' + 'top:0;' + 'z-index:-1;';
          body.appendChild(sizingImage);
        }
      }
    }, {
      key: 'stop',
      value: function stop() {
        var image = this.image;


        image.onload = null;
        image.onerror = null;
        image.parentNode.removeChild(image);
        this.image = null;
      }
    }, {
      key: 'build',
      value: function build() {
        if (!this.sized || this.ready) {
          return;
        }

        var element = this.element,
            options = this.options,
            image = this.image;

        // Create cropper elements

        var container = element.parentNode;
        var template = document.createElement('div');

        template.innerHTML = TEMPLATE;

        var cropper = template.querySelector('.' + NAMESPACE + '-container');
        var canvas = cropper.querySelector('.' + NAMESPACE + '-canvas');
        var dragBox = cropper.querySelector('.' + NAMESPACE + '-drag-box');
        var cropBox = cropper.querySelector('.' + NAMESPACE + '-crop-box');
        var face = cropBox.querySelector('.' + NAMESPACE + '-face');

        this.container = container;
        this.cropper = cropper;
        this.canvas = canvas;
        this.dragBox = dragBox;
        this.cropBox = cropBox;
        this.viewBox = cropper.querySelector('.' + NAMESPACE + '-view-box');
        this.face = face;

        canvas.appendChild(image);

        // Hide the original image
        addClass(element, CLASS_HIDDEN);

        // Inserts the cropper after to the current image
        container.insertBefore(cropper, element.nextSibling);

        // Show the image if is hidden
        if (!this.isImg) {
          removeClass(image, CLASS_HIDE);
        }

        this.initPreview();
        this.bind();

        options.aspectRatio = Math.max(0, options.aspectRatio) || NaN;
        options.viewMode = Math.max(0, Math.min(3, Math.round(options.viewMode))) || 0;

        addClass(cropBox, CLASS_HIDDEN);

        if (!options.guides) {
          addClass(cropBox.getElementsByClassName(NAMESPACE + '-dashed'), CLASS_HIDDEN);
        }

        if (!options.center) {
          addClass(cropBox.getElementsByClassName(NAMESPACE + '-center'), CLASS_HIDDEN);
        }

        if (options.background) {
          addClass(cropper, NAMESPACE + '-bg');
        }

        if (!options.highlight) {
          addClass(face, CLASS_INVISIBLE);
        }

        if (options.cropBoxMovable) {
          addClass(face, CLASS_MOVE);
          setData(face, DATA_ACTION, ACTION_ALL);
        }

        if (!options.cropBoxResizable) {
          addClass(cropBox.getElementsByClassName(NAMESPACE + '-line'), CLASS_HIDDEN);
          addClass(cropBox.getElementsByClassName(NAMESPACE + '-point'), CLASS_HIDDEN);
        }

        this.render();
        this.ready = true;
        this.setDragMode(options.dragMode);

        if (options.autoCrop) {
          this.crop();
        }

        this.setData(options.data);

        if (isFunction(options.ready)) {
          addListener(element, EVENT_READY, options.ready, {
            once: true
          });
        }

        dispatchEvent(element, EVENT_READY);
      }
    }, {
      key: 'unbuild',
      value: function unbuild() {
        if (!this.ready) {
          return;
        }

        this.ready = false;
        this.unbind();
        this.resetPreview();
        this.cropper.parentNode.removeChild(this.cropper);
        removeClass(this.element, CLASS_HIDDEN);
      }
    }, {
      key: 'uncreate',
      value: function uncreate() {
        var element = this.element;


        if (this.ready) {
          this.unbuild();
          this.ready = false;
          this.cropped = false;
        } else if (this.sizing) {
          this.sizingImage.onload = null;
          this.sizing = false;
          this.sized = false;
        } else if (this.reloading) {
          this.xhr.abort();
        } else if (this.isImg) {
          if (element.complete) {
            clearTimeout(this.timeout);
          } else {
            removeListener(element, EVENT_LOAD, this.onStart);
          }
        } else if (this.image) {
          this.stop();
        }
      }

      /**
       * Get the no conflict cropper class.
       * @returns {Cropper} The cropper class.
       */

    }], [{
      key: 'noConflict',
      value: function noConflict() {
        window.Cropper = AnotherCropper;
        return Cropper;
      }

      /**
       * Change the default options.
       * @param {Object} options - The new default options.
       */

    }, {
      key: 'setDefaults',
      value: function setDefaults(options) {
        assign(DEFAULTS, isPlainObject(options) && options);
      }
    }]);
    return Cropper;
  }();

  assign(Cropper.prototype, render, preview, events, handlers, change, methods);

  if ($.fn) {
    var AnotherCropper$1 = $.fn.cropper;
    var NAMESPACE$1 = 'cropper';

    $.fn.cropper = function jQueryCropper(option) {
      for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      var result = void 0;

      this.each(function (i, element) {
        var $element = $(element);
        var isDestroy = option === 'destroy';
        var cropper = $element.data(NAMESPACE$1);

        if (!cropper) {
          if (isDestroy) {
            return;
          }

          var options = $.extend({}, $element.data(), $.isPlainObject(option) && option);

          cropper = new Cropper(element, options);
          $element.data(NAMESPACE$1, cropper);
        }

        if (typeof option === 'string') {
          var fn = cropper[option];

          if ($.isFunction(fn)) {
            result = fn.apply(cropper, args);

            if (result === cropper) {
              result = undefined;
            }

            if (isDestroy) {
              $element.removeData(NAMESPACE$1);
            }
          }
        }
      });

      return result !== undefined ? result : this;
    };

    $.fn.cropper.Constructor = Cropper;
    $.fn.cropper.setDefaults = Cropper.setDefaults;
    $.fn.cropper.noConflict = function noConflict() {
      $.fn.cropper = AnotherCropper$1;
      return this;
    };
  }

})));

/**
 * Created by jong on 7/29/15.
 */

var ILabCrop=function($,settings){
    this.settings=settings;
    this.modalContainer=$('#ilabm-container-'+settings.modal_id);
    this.cropper=this.modalContainer.find('.ilabc-cropper');
    this.cropperData={};
    this.modal_id=settings.modal_id;

    this.modalContainer.find('.ilabm-editor-tabs').ilabTabs({
        currentValue: this.settings.size,
        tabSelected:function(tab){
            ILabModal.loadURL(tab.data('url'),true,function(response){
                this.bindUI(response);
            }.bind(this));
        }.bind(this)
    });

    this.modalContainer.find('.ilabc-button-crop').on('click',function(e){
        e.preventDefault();
        this.crop();
        return false;
    }.bind(this));

    this.updatePreviewWidth=function() {
        var width =  this.modalContainer.find('.ilab-crop-preview-title').width();
        this.modalContainer.find('.ilab-crop-preview').css({
            'height' : (width / this.settings.aspect_ratio) + 'px',
            'width' : width + 'px'
        });
    }.bind(this);

    this.bindUI=function(settings){
        console.log(settings);

        this.settings=settings;

        this.cropper.cropper('destroy');
        this.cropper.off('built.cropper');

        if (settings.hasOwnProperty('cropped_src') && settings.cropped_src !== null)
        {
            this.modalContainer.find('.ilab-current-crop-img').attr('src',settings.cropped_src);
        }

        if (settings.hasOwnProperty('size_title') && (settings.size_title !== null))
        {
            this.modalContainer.find('.ilabc-crop-size-title').text("Current "+settings.size_title+" ("+settings.min_width+" x "+settings.min_height+")");
        }

        if (typeof settings.aspect_ratio !== 'undefined')
        {
            this.updatePreviewWidth();

            if ((typeof settings.prev_crop_x !== 'undefined') && (settings.prev_crop_x !== null)) {
                this.cropperData = {
                    x : settings.prev_crop_x,
                    y : settings.prev_crop_y,
                    width : settings.prev_crop_width,
                    height : settings.prev_crop_height
                };
            }

            this.cropper.on('built.cropper',function(){
                this.updatePreviewWidth();
            }.bind(this)).on('crop.cropper',function(e){
                //console.log(e.x, e.y, e.width, e.height);
            }).cropper({
                viewMode: 1,
                dragMode: 'none',
                aspectRatio : settings.aspect_ratio,
                minWidth : settings.min_width,
                minHeight : settings.min_height,
                modal : true,
                movable: false,
                cropBoxMovable: true,
                zoomable: false,
                zoomOnWheel: false,
                zoomOnTouch: false,
                autoCropArea: 1,
                data : this.cropperData,
                checkImageOrigin: false,
                checkCrossOrigin: false,
                responsive: true,
                restore: true,
                preview: '#ilabm-container-'+this.modal_id+' .ilab-crop-preview'
            });
        }
    }.bind(this);

    this.crop=function(){
        this.displayStatus('Saving crop ...');

        var data = this.cropper.cropper('getData');
        data['action'] = 'ilab_perform_crop';
        data['post'] = this.settings.image_id;
        data['size'] = this.settings.size;
        jQuery.post(ajaxurl, data, function(response) {
            if (response.status=='ok') {
                this.modalContainer.find('.ilab-current-crop-img').one('load',function(){
                   this.hideStatus();
                }.bind(this));
                this.modalContainer.find('.ilab-current-crop-img').attr('src', response.src);
            }
            else
                this.hideStatus();
        }.bind(this));
    }.bind(this);

    this.displayStatus=function(message){
        this.modalContainer.find('.ilabm-status-label').text(message);
        this.modalContainer.find('.ilabm-status-container').removeClass('is-hidden');
    }.bind(this);

    this.hideStatus=function(){
        this.modalContainer.find('.ilabm-status-container').addClass('is-hidden');
    }.bind(this);

    this.bindUI(settings);
};

/**
 * Created by jong on 8/8/15.
 */

var ImgixComponents=(function(){
    var byteToHex=function(byte) {
        var hexChar = ["0", "1", "2", "3", "4", "5", "6", "7","8", "9", "A", "B", "C", "D", "E", "F"];
        return hexChar[(byte >> 4) & 0x0f] + hexChar[byte & 0x0f];
    };

    return {
        utilities: {
          byteToHex:byteToHex
      }
    };
})();
(function($){

    $.fn.imgixLabel=function(options){
        var settings= $.extend({},options);

        return this.each(function(){
            var label=$(this);

            var changeTimerId;

            var currentVal=0;

            var textInput=$('<input type="text" class="imgix-label-editor is-hidden" pattern="[0-9-]+">');
            label.parent().append(textInput);

            textInput.on('keydown',function(e){
                if (e.keyCode==27) {
                    textInput.off('blur');
                    textInput.off('input');

                    textInput.addClass('is-hidden');
                    if (settings.hasOwnProperty('changed'))
                        settings.changed(currentVal);

                    label.text(currentVal);
                }
                else if (e.keyCode==13) {
                    textInput.off('blur');
                    textInput.off('input');

                    var val=parseInt(textInput.val());
                    textInput.addClass('is-hidden');
                    if (settings.hasOwnProperty('changed'))
                        settings.changed(val);

                    label.text(val);
                }
                else if (e.keyCode==38) {
                    var val=parseInt(textInput.val());
                    val++;
                    textInput.val(val);
                    if (settings.hasOwnProperty('changed'))
                        settings.changed(val);
                    label.text(val);
                }
                else if (e.keyCode==40) {
                    var val=parseInt(textInput.val());
                    val--;
                    textInput.val(val);
                    if (settings.hasOwnProperty('changed'))
                        settings.changed(val);
                    label.text(val);

                }
                else {
                    if (e.keyCode<57)
                        return true;
                    else if ((e.keyCode>90) && (e.keyCode<105))
                        return true;
                    else if (e.keyCode==109)
                        return true;
                    else if (e.metaKey)
                        return true;

                    e.preventDefault();
                    return false;
                }
            });

            label.on('click',function(e){
                e.preventDefault();

                textInput.on('input',function(){
                    var val=parseInt(textInput.val());
                    if (settings.hasOwnProperty('changed'))
                    {
                        clearTimeout(changeTimerId);
                        changeTimerId = setTimeout(function(){
                            settings.changed(val);
                        }, 500);
                    }

                    label.text(val);
                });

                textInput.on('blur',function(){
                    var val=parseInt(textInput.val());
                    textInput.addClass('is-hidden');
                    if (settings.hasOwnProperty('changed'))
                        settings.changed(val);

                    label.text(val);
                });

                currentVal=(settings.hasOwnProperty('currentValue')) ? settings.currentValue() : 0;
                textInput.val(currentVal);
                textInput.removeClass('is-hidden');
                textInput.select();
                textInput.focus();

                return false;
            });
        });
    };

}(jQuery));


(function($){
    ImgixComponents.ImgixSlider=function(delegate, container)
    {
        this.delegate=delegate;
        this.container=container;
        this.valueLabel=container.find('.imgix-param-title-right > h3');
        this.slider=container.find('.imgix-param');
        this.resetButton=container.find('.imgix-param-reset');

        this.defaultValue=container.data('default-value');
        this.param=container.data('param');

        var sliderRef=this;

        this.container.find('.imgix-param-label').imgixLabel({
            currentValue:function(){
                return sliderRef.slider.val();
            },
            changed:function(newVal){
                if (newVal==sliderRef.slider.val())
                    return;

                sliderRef.slider.val(newVal);
                $(document).trigger(sliderRef.param+'-changed', [newVal]);
                sliderRef.slider.hide().show(0);
                sliderRef.delegate.preview();
            }
        });

        this.resetButton.on('click',function(){
            sliderRef.reset();
        });

        this.slider.on('input',function(){
            sliderRef.valueLabel.text(sliderRef.slider.val());
        });

        this.slider.on('change',function(){
            sliderRef.valueLabel.text(sliderRef.slider.val());
            sliderRef.delegate.preview();

            $(document).trigger(sliderRef.param+'-changed', [sliderRef.slider.val()]);
        });

        $(document).on('change-'+sliderRef.param, function(evt, newValue) {
           sliderRef.slider.val(newValue);
           sliderRef.valueLabel.text(newValue);
        });
    };

    ImgixComponents.ImgixSlider.prototype.destroy=function() {
        this.slider.off('input');
        this.slider.off('change');
        this.resetButton.off('click');
    };

    ImgixComponents.ImgixSlider.prototype.reset=function(data) {
        var val;

        if (data && data.hasOwnProperty(this.param))
            val=data[this.param];
        else
            val=this.defaultValue;

        this.valueLabel.text(val);
        this.slider.val(val);
        this.slider.hide().show(0);

        this.delegate.preview();
    };

    ImgixComponents.ImgixSlider.prototype.saveValue=function(data) {
        if (this.slider.val()!=this.defaultValue)
            data[this.param]=this.slider.val();

        return data;
    };

}(jQuery));

(function($){

    ImgixComponents.ImgixColor=function(delegate, container)
    {
        this.color;
        this.opacity;
        this.hasOpacity = false;

        this.delegate=delegate;
        this.container=container;

        this.type=container.data('param-type');
        this.resetButton=container.find('.imgix-param-reset');
        this.param=container.data('param');
        this.defaultValue=container.data('default-value');

        var colorPickerRef=this;

        var minicolor = null;

        if (this.type=='blend-color') {
            this.blendParam=container.data('blend-param');
            this.blendSelect = container.find('.imgix-param-blend');

            var currentBlend=container.data('blend-value');
            this.blendSelect.val(currentBlend);

            this.blendSelect.on('change',function(){
                colorPickerRef.delegate.preview();
            });
        } else {
            this.blendSelect = null;
        }



        container.find('.ilab-color-input').each(function(){
            colorPickerRef.hasOpacity = (($(this).data('opacity') != null) && ($(this).data('opacity') !== false));

            colorPickerRef.color = $(this).val().replace('#', '');
            colorPickerRef.opacity = (colorPickerRef.hasOpacity) ? $(this).data('opacity') : 0;

            $(this).minicolors({
                format: 'hex',
                position: 'bottom right',
                opacity: colorPickerRef.hasOpacity ? "'"+$(this).data('opacity')+"'" : false,
                change:function(newColor, newOpacity) {
                    var oldOpacity = colorPickerRef.opacity;

                    colorPickerRef.color = newColor.replace('#', '');
                    colorPickerRef.opacity = newOpacity;

                    if (colorPickerRef.hasOpacity) {
                        if ((colorPickerRef.opacity > 0) || (oldOpacity != colorPickerRef.opacity)) {
                            colorPickerRef.delegate.preview();
                        }
                    } else {
                        colorPickerRef.delegate.preview();
                    }
                }
            });

            colorPickerRef.minicolor = $(this);
        });

        this.resetButton.on('click',function(){
            colorPickerRef.reset();
        });
    };

    ImgixComponents.ImgixColor.prototype.destroy=function() {
        if (this.type=='blend-color') {
            this.blendSelect.off('change');
        }
        this.resetButton.off('click');
    };

    ImgixComponents.ImgixColor.prototype.reset=function(data) {
        var blend='none';
        var val;

        if ((data !== undefined) && data.hasOwnProperty(this.blendParam))
        {
            blend=data[this.blendParam];
        }

        if ((data !== undefined) && data.hasOwnProperty(this.param))
        {
            val=data[this.param];
        }
        else
            val=this.defaultValue;

        val=val.replace('#','');
        if (val.length==8)
        {
            this.opacity=parseInt('0x'+val.substring(0,2))/255.0;
            val = val.substring(2);
        }

        this.color = val;
        this.minicolor.minicolors('value', '#'+this.color);
        if (this.hasOpacity) {
            this.minicolor.minicolors('opacity', this.opacity);
        }

        if (this.type=='blend-color') {
            this.blendSelect.val(blend);
        }

        this.delegate.preview();
    };

    ImgixComponents.ImgixColor.prototype.saveValue=function(data) {
        if (this.hasOpacity) {
            if (this.opacity > 0) {
                data[this.param] = '#'+ImgixComponents.utilities.byteToHex(Math.round(parseFloat(this.opacity) * 255.0))+this.color;
                if (this.type == 'blend-color') {
                    if (this.blendSelect && (this.blendSelect.val() != 'none')) {
                        data[this.blendParam] = this.blendSelect.val();
                    }
                }
            }
        } else {
            data[this.param] = '#'+this.color;
        }

        return data;
    };

}(jQuery));

(function($){

    ImgixComponents.ImgixAlignment=function(delegate, container)
    {
        this.delegate=delegate;
        this.container=container;
        this.alignmentParam=container.find('.imgix-param');
        this.resetButton=container.find('.imgix-param-reset');
        this.defaultValue=container.data('default-value');
        this.param=container.data('param');

        var alignmentRef=this;

        this.resetButton.on('click',function(){
            alignmentRef.reset();
        });

        container.find('.imgix-alignment-button').on('click',function(){
            var button=$(this);
            alignmentRef.container.find('.imgix-alignment-button').each(function(){
                $(this).removeClass('selected-alignment');
            });

            button.addClass('selected-alignment');
            alignmentRef.alignmentParam.val(button.data('param-value'));
            alignmentRef.delegate.preview();
        });
    };

    ImgixComponents.ImgixAlignment.prototype.destroy=function() {
        this.resetButton.off('click');
        this.container.find('.imgix-alignment-button').off('click');
    };

    ImgixComponents.ImgixAlignment.prototype.reset=function(data) {
        var val;

        if (data && data.hasOwnProperty(this.param))
            val=data[this.param];
        else
            val=this.defaultValue;

        if (val=='')
            val=this.defaultValue;

        this.container.find('.imgix-alignment-button').each(function(){
            var button=$(this);
            if (button.data('param-value')==val)
                button.addClass('selected-alignment');
            else
                button.removeClass('selected-alignment');
        });

        this.alignmentParam.val(val);
        this.delegate.preview();
    };

    ImgixComponents.ImgixAlignment.prototype.saveValue=function(data) {
        if (this.alignmentParam.val()!=this.defaultValue)
            data[this.param]=this.alignmentParam.val();

        return data;
    };
}(jQuery));

(function($){

    ImgixComponents.ImgixMediaChooser=function(delegate, container)
    {
        this.delegate=delegate;
        this.container=container;
        this.preview=container.find('.imgix-media-preview img');
        this.mediaInput=container.find('.imgix-param');
        this.selectButton=container.find('.imgix-media-button');
        this.resetButton=container.find('.imgix-param-reset');

        this.defaultValue=container.data('default-value');
        this.param=container.data('param');

        this.uploader=wp.media({
            title: 'Select Watermark',
            button: {
                text: 'Select Watermark'
            },
            multiple: false
        });

        var mediaRef=this;

        this.resetButton.on('click',function(){
            mediaRef.reset();
        });

        this.uploader.on('select', function() {
            attachment = mediaRef.uploader.state().get('selection').first().toJSON();
            mediaRef.mediaInput.val(attachment.id);
            mediaRef.preview.attr('src',attachment.url);

            mediaRef.delegate.preview();
        });

        this.selectButton.on('click',function(e){
            e.preventDefault();
            mediaRef.uploader.open();
            return false;
        });

    };

    ImgixComponents.ImgixMediaChooser.prototype.destroy=function() {
        this.selectButton.off('click');
        this.uploader.off('select');
        this.resetButton.off('click');
    };

    ImgixComponents.ImgixMediaChooser.prototype.reset=function(data) {
        var val;

        if (data && data.hasOwnProperty(this.param))
        {
            val=data[this.param];
            this.mediaInput.val(val);
        }
        else
            this.mediaInput.val('');

        if (data && data.hasOwnProperty(this.param+'_url'))
        {
            this.preview.attr('src',data[this.param+'_url']);
        }
        else
        {
            this.preview.removeAttr('src').replaceWith(this.preview.clone());
            this.preview=this.container.find('.imgix-media-preview img');
        }

        this.delegate.preview();
    };

    ImgixComponents.ImgixMediaChooser.prototype.saveValue=function(data) {
        var val=this.mediaInput.val();

        if (val && val!='')
            data[this.param]=val;

        return data;
    };

}(jQuery));


(function($){
    ImgixComponents.ImgixPillbox=function(delegate, container)
    {
        this.delegate=delegate;
        this.container=container;
        this.param=container.data('param');
        this.values=container.data('param-values').split(',');
        this.buttons=container.find('.ilabm-pill');
        this.inputs={};
        this.radioMode = container.data('radio-mode');
        this.mustSelect = container.data('must-select');

        var pillboxRef=this;

        this.deselectOthers = function(targetButton) {
            this.buttons.each(function(){
                if (targetButton == this) {
                    return;
                }

                var button = $(this);
                var valueName=button.data('param');
                pillboxRef.inputs[valueName].val(0);

                button.removeClass('pill-selected');
                $(document).trigger(valueName+'-deselected');
            });
        }.bind(this);

        this.buttons.each(function(){
            var button=$(this);
            var valueName=button.data('param');
            pillboxRef.inputs[valueName]=pillboxRef.container.find("input[name='"+valueName+"']");
            button.on('click',function(e){
                e.preventDefault();

                if (pillboxRef.inputs[valueName].val()==0)
                {
                    pillboxRef.inputs[valueName].val(1);
                    button.addClass('pill-selected');

                    $(document).trigger(valueName+'-selected');

                    if (pillboxRef.radioMode) {
                        pillboxRef.deselectOthers(this);
                    }
                }
                else if (!pillboxRef.mustSelect)
                {
                    pillboxRef.inputs[valueName].val(0);
                    button.removeClass('pill-selected');

                    $(document).trigger(valueName+'-deselected');
                }

                pillboxRef.delegate.preview();

                return false;
            });

            $(document).on('change-'+valueName,function(evt, newValue){
                pillboxRef.inputs[valueName].val((newValue) ? 1 : 0);
                if (newValue) {
                    button.addClass('pill-selected');
                } else {
                    button.removeClass('pill-selected');
                }
            });
        });

    };

    ImgixComponents.ImgixPillbox.prototype.destroy=function() {
        this.buttons.off('click');
    };

    ImgixComponents.ImgixPillbox.prototype.reset=function(data) {
        this.buttons.each(function(){
           $(this).removeClass('pill-selected');
        });

        var pillboxRef=this;
        Object.keys(this.inputs).forEach(function(key,index){
            pillboxRef.inputs[key].val(0);
        });

        if (data && data.hasOwnProperty(this.param)) {
            var val = data[this.param].split(',');


            val.forEach(function (key, index) {
                pillboxRef.inputs[key].val(1);
                pillboxRef.container.find('imgix-pill-' + key).addClass('pill-selected');
            });
        }

        this.delegate.preview();
    };

    ImgixComponents.ImgixPillbox.prototype.saveValue=function(data) {
        var vals=[];

        var pillboxRef=this;
        Object.keys(this.inputs).forEach(function(key,index){
            if (pillboxRef.inputs[key].val()==1)
                vals.push(key);
        });

        if (vals.length>0)
            data[this.param]=vals.join(',');

        return data;
    };

}(jQuery));

/**
 * Created by jong on 8/9/15.
 */

var ILabImgixPresets=function($,delegate,container) {

    this.delegate=delegate;
    this.container=container.find('.ilabm-bottom-bar');
    this.presetSelect=this.container.find('.imgix-presets');
    this.presetContainer=this.container.find('.imgix-preset-container');
    this.presetDefaultCheckbox=this.container.find('.imgix-preset-make-default');

    var self=this;

    self.presetSelect.on('change',function(){
        if (self.presetSelect.val==0)
        {
            self.delegate.resetAll();
            self.presetDefaultCheckbox.prop('checked',false);
            return;
        }

        var preset=self.delegate.settings.presets[self.presetSelect.val()];
        if (preset.default_for==self.delegate.settings.size)
            self.presetDefaultCheckbox.prop('checked',true);

        self.delegate.bindPreset(preset);
    });

    this.container.find('.imgix-new-preset-button').on('click',function(){
        self.newPreset();
    });

    this.container.find('.imgix-save-preset-button').on('click',function(){
        self.savePreset();
    });

    this.container.find('.imgix-delete-preset-button').on('click',function(){
        self.deletePreset();
    });

    this.init=function() {
        self.presetSelect.find('option').remove();

        if (Object.keys(self.delegate.settings.presets).length==0)
        {
            self.presetContainer.addClass('is-hidden');
        }
        else
        {
            Object.keys(self.delegate.settings.presets).forEach(function(key,index) {
                self.presetSelect.append($('<option></option>')
                    .attr("value",'0')
                    .text('None'));

                self.presetSelect.append($('<option></option>')
                    .attr("value",key)
                    .text(self.delegate.settings.presets[key].title));
            });

            self.presetContainer.removeClass('is-hidden');
            self.presetSelect.val(self.delegate.settings.currentPreset);
        }
    };

    this.clearSelected=function(){
        self.presetSelect.val(0);
        self.presetDefaultCheckbox.prop('checked',false);
    };

    this.setCurrentPreset=function(preset, is_default){
        if (is_default)
            self.presetDefaultCheckbox.prop('checked',true);
        else
            self.presetDefaultCheckbox.prop('checked',false);

        self.presetSelect.val(preset);
    };

    this.newPreset=function(){
        var name=prompt("New preset name");
        if (name!=null)
        {
            self.delegate.displayStatus('Saving preset ...');

            var data={};
            data['name']=name;
            if (self.presetDefaultCheckbox.is(':checked'))
                data['make_default']=1;

            self.delegate.postAjax('ilab_dynamic_images_new_preset', data, function(response) {
                self.delegate.hideStatus();
                if (response.status=='ok')
                {
                    self.delegate.settings.currentPreset=response.currentPreset;
                    self.delegate.settings.presets=response.presets;

                    self.init();
                }
            });
        }
    };

    this.savePreset=function(){
        if (self.presetSelect.val()==null)
            return;

        self.delegate.displayStatus('Saving preset ...');

        var data={};
        data['key']=self.presetSelect.val();
        if (self.presetDefaultCheckbox.is(':checked'))
            data['make_default']=1;

        self.delegate.postAjax('ilab_dynamic_images_save_preset', data, function(response) {
            self.delegate.hideStatus();
        });
    };

    this.deletePreset=function(){
        if (self.presetSelect.val()==null)
            return;

        if (!confirm("Are you sure you want to delete this preset?"))
            return;

        self.delegate.displayStatus('Delete preset ...');

        var data={};
        data['key']=self.presetSelect.val();

        self.delegate.postAjax('ilab_dynamic_images_delete_preset', data, function(response) {
            self.delegate.hideStatus();
            if (response.status=='ok')
            {
                self.delegate.settings.currentPreset=response.currentPreset;
                self.delegate.settings.presets=response.presets;

                self.init();

                self.delegate.bindUI(response);
            }
        });
    };

    this.init();
};
(function($){

    $.fn.ilabSidebarTabs=function(options){
        var settings= $.extend({},options);

        var firstTab=false;
        return this.find('.ilabm-sidebar-tab').each(function(){
            var tab=$(this);
            var target=settings.container.find('.'+tab.data('target'));

            if (!firstTab)
            {
                tab.addClass('active-tab');
                target.removeClass('is-hidden');

                firstTab=true;
            }

            tab.on('click',function(e){
                e.preventDefault();

                settings.container.find(".ilabm-sidebar-tab").each(function() {
                    var otherTab = $(this);
                    var tabTarget = settings.container.find('.' + otherTab.data('target'));

                    otherTab.removeClass('active-tab');
                    tabTarget.addClass('is-hidden');
                });

                tab.addClass('active-tab');
                target.removeClass('is-hidden');

                return false;
            });
        });
    };

}(jQuery));

/**
 *
 * @param {jQuery} $
 * @param {ILabImageEdit} imgixEditor
 * @constructor
 */
var ILabFaceEditor=function($, imgixEditor){
    //region Variables/Setup
    var faces = [];
    var allFaces = null;
    var currentFaceIndex = 0;

    if (imgixEditor.settings.meta.hasOwnProperty('faces')) {
        var faceLeft = Number.MAX_VALUE;
        var faceTop = Number.MAX_VALUE;
        var faceRight = 0;
        var faceBottom = 0;

        var faceIndex = 0;
        while(true) {
            if (!imgixEditor.settings.meta.faces.hasOwnProperty(faceIndex)) {
                break;
            }

            var face = imgixEditor.settings.meta.faces[faceIndex];

            faceLeft = Math.min(faceLeft, face.BoundingBox.Left);
            faceTop = Math.min(faceTop, face.BoundingBox.Top);
            faceRight = Math.max(faceRight, face.BoundingBox.Left + face.BoundingBox.Width);
            faceBottom = Math.max(faceBottom, face.BoundingBox.Top + face.BoundingBox.Height);

            var faceData = {
                index: faceIndex + 1,
                left: face.BoundingBox.Left,
                top: face.BoundingBox.Top,
                right: face.BoundingBox.Left + face.BoundingBox.Width,
                bottom: face.BoundingBox.Top + face.BoundingBox.Height,
                width: face.BoundingBox.Width,
                height: face.BoundingBox.Height,
                element: $("<div class='ilab-face-outline hidden'><span>"+(faceIndex+1)+"</span></div>")
            };

            faces.push(faceData);

            var self = this;
            (function(fi){
                faceData.element.on('click', function(e){
                    currentFaceIndex = fi;
                    self.displayFaces();
                    $(document).trigger('change-faceindex', [fi]);
                    imgixEditor.preview();
                });
            })(faceIndex + 1);

            imgixEditor.editorArea.append(faceData.element);

            faceIndex++;
        }


        if (faces.length > 1) {
            var faceWidth = faceRight - faceLeft;
            var faceHeight = faceBottom - faceTop;
            allFaces = {
                left: faceLeft,
                top: faceTop,
                right: faceRight,
                bottom: faceBottom,
                width: faceWidth,
                height: faceHeight,
                element: $("<div class='ilab-all-faces-outline hidden'></div>")
            };

            imgixEditor.editorArea.append(allFaces.element);
        } else {
            allFaces = null;
        }
    }
    //endregion

    //region Methods
    this.updateFacePositions = function() {
        var cb = imgixEditor.editorArea.get(0).getBoundingClientRect();
        var imageCb = imgixEditor.previewImage.get(0).getBoundingClientRect();

        var imageRect = {
            top: imageCb.top - cb.top,
            left: imageCb.left - cb.left,
            right: imageCb.right - cb.left,
            bottom: imageCb.bottom - cb.top,
            width: imageCb.width,
            height: imageCb.height
        };

        if (allFaces != null) {
            var allL = imageRect.left + (imageRect.width * allFaces.left);
            var allT = imageRect.top + (imageRect.height * allFaces.top);
            var allW = imageRect.width * allFaces.width;
            var allH = imageRect.height * allFaces.height;

            allFaces.element.css({
                'left': allL+'px',
                'top': allT+'px',
                'width': allW+'px',
                'height': allH+'px'
            });
        }

        faces.forEach(function(face){
            var allL = imageRect.left + (imageRect.width * face.left);
            var allT = imageRect.top + (imageRect.height * face.top);
            var allW = imageRect.width * face.width;
            var allH = imageRect.height * face.height;

            face.element.css({
                'left': allL+'px',
                'top': allT+'px',
                'width': allW+'px',
                'height': allH+'px'
            });
        });
    };

    this.displayFaces=function() {
        this.updateFacePositions();

        if (currentFaceIndex == 0) {
            if (faces.length == 1) {
                faces[0].element.removeClass('hidden');
                faces[0].element.addClass('active');
            } else {
                if (allFaces != null) {
                    allFaces.element.removeClass('hidden');
                }

                faces.forEach(function(face){
                    face.element.addClass('hidden');
                });
            }
        } else {
            if (allFaces != null) {
                allFaces.element.addClass('hidden');
            }

            faces.forEach(function(face, index){
               face.element.removeClass('hidden');
               face.element.removeClass('active');
               if (index == currentFaceIndex - 1) {
                   face.element.addClass('active');
               }
            });
        }
    };

    this.hideFaces=function() {
        if (allFaces != null) {
            allFaces.element.addClass('hidden');
        }

        faces.forEach(function(face){
            face.element.addClass('hidden');
        });
    };

    this.disable = function() {
        $(document).trigger('change-usefaces', [false]);
        this.hideFaces();
    };

    this.save = function(postData) {
        return postData;
    };

    //endregion

    //region UI Events
    $(document).on('usefaces-selected', function(e){
        if (faces.length == 0) {
            alert("No faces have been detected in this image.  To use this feature, you will need to have the Rekognition tool enabled if it isn't already.");
            $(document).trigger('change-usefaces', [false]);
            return;
        }

        $(document).trigger('change-entropy', [false]);
        $(document).trigger('change-edges', [false]);
        imgixEditor.focalPointEditor.disable();
        this.displayFaces();
    }.bind(this));

    $(document).on('usefaces-deselected', function(e){
        this.hideFaces();
    }.bind(this));

    $(document).on('faceindex-changed', function(event, newIndex) {
        currentFaceIndex = newIndex;
        this.displayFaces();
    }.bind(this));

    $(window).on('resize', function(){
        this.updateFacePositions();
    }.bind(this));
    //endregion

    //region Startup
    if (imgixEditor.settings.settings.hasOwnProperty('focalpoint')) {
        if (imgixEditor.settings.settings.focalpoint == 'usefaces') {
            if (imgixEditor.settings.settings.hasOwnProperty('faceindex')) {
                currentFaceIndex = imgixEditor.settings.settings.faceindex;
            }

            setTimeout(function(){
                this.updateFacePositions();
                this.displayFaces();
            }.bind(this), 300);
        }
    }
    //endregion
};
/**
 * Focal Point Editor
 * @param {jQuery} $
 * @param {ILabImageEdit} imgixEditor
 * @constructor
 */
var ILabFocalPointEditor=function($, imgixEditor){
    //region Variables
    var focalPointIcon = $('<div class="ilabm-focal-point-icon"></div>');

    var focalPointX = 0.5;
    var focalPointY = 0.5;
    if (imgixEditor.settings.settings.hasOwnProperty('fp-x')) {
        focalPointX = imgixEditor.settings.settings['fp-x'];
    }
    if (imgixEditor.settings.settings.hasOwnProperty('fp-y')) {
        focalPointY = imgixEditor.settings.settings['fp-y'];
    }

    var canSetFocalPoint = false;
    //endregion

    //region Methods
    this.updateFocalPointPosition = function() {
        var cb = imgixEditor.editorArea.get(0).getBoundingClientRect();
        var imageCb = imgixEditor.previewImage.get(0).getBoundingClientRect();

        var imageRect = {
            top: imageCb.top - cb.top,
            left: imageCb.left - cb.left,
            right: imageCb.right - cb.left,
            bottom: imageCb.bottom - cb.top,
            width: imageCb.width,
            height: imageCb.height
        };

        var l = imageRect.left + (imageRect.width * focalPointX);
        var t = imageRect.top + (imageRect.height * focalPointY);

        l -= 12;
        t -= 12;

        focalPointIcon.css({
            "left": l + 'px',
            "top": t + 'px'
        });
    };

    this.buildFocalPoint=function() {
        focalPointIcon.remove();
        imgixEditor.editorArea.append(focalPointIcon);
        this.updateFocalPointPosition();
    };
    this.disable = function() {
        $(document).trigger('change-focalpoint', [false]);
        canSetFocalPoint = false;
        focalPointIcon.remove();
    };

    this.save = function(postData) {
        if (postData.hasOwnProperty('focalpoint')) {
            postData['fp-x'] = focalPointX;
            postData['fp-y'] = focalPointY;
        }

        return postData;
    };
    //endregion

    //region UI Events
    $(document).on('focalpoint-selected', function(e){
        $(document).trigger('change-entropy', [false]);
        $(document).trigger('change-edges', [false]);
        imgixEditor.faceEditor.disable();
        canSetFocalPoint = true;
        this.buildFocalPoint();
    }.bind(this));

    $(document).on('focalpoint-deselected', function(e){
        canSetFocalPoint = false;
        focalPointIcon.remove();
    }.bind(this));

    imgixEditor.editorArea.on('mousedown', function(e){
        e.preventDefault();

        if (!canSetFocalPoint) {
            return false;
        }

        this.buildFocalPoint();

        var cb = imgixEditor.editorArea.get(0).getBoundingClientRect();
        var imageCb = imgixEditor.previewImage.get(0).getBoundingClientRect();

        var imageRect = {
            top: imageCb.top - cb.top,
            left: imageCb.left - cb.left,
            right: imageCb.right - cb.left,
            bottom: imageCb.bottom - cb.top,
            width: imageCb.width,
            height: imageCb.height
        };

        imgixEditor.editorArea.on('mousemove', function(e){
            e.preventDefault();

            var l = (e.clientX - cb.left);
            if (l<imageRect.left) {
                l = imageRect.left;
            } else if (l>imageRect.right) {
                l = imageRect.right;
            }

            var t = (e.clientY - cb.top);
            if (t<imageRect.top) {
                t = imageRect.top;
            } else if (t>imageRect.bottom) {
                t = imageRect.bottom;
            }

            focalPointX = (l-imageRect.left) / imageRect.width;
            focalPointY = (t-imageRect.top) / imageRect.height;

            l -= 12;
            t -= 12;

            focalPointIcon.css({
                "left": l + 'px',
                "top": t + 'px'
            });

            return false;
        }.bind(this));

        imgixEditor.editorArea.on('mouseup', function(e){
            e.preventDefault();
            imgixEditor.editorArea.off('mouseup');
            imgixEditor.editorArea.off('mousemove');
            imgixEditor.preview();
            return false;
        }.bind(this));

        return false;
    }.bind(this));

    $(window).on('resize', function(){
        this.updateFocalPointPosition();
    }.bind(this));
    //endregion

    //region Startup
    if (imgixEditor.settings.settings.hasOwnProperty('focalpoint')) {
        if (imgixEditor.settings.settings.focalpoint == 'focalpoint') {
            canSetFocalPoint = true;
            setTimeout(function(){
                this.buildFocalPoint();
            }.bind(this), 300);
        }
    }
    //endregion
};
/**
 * Image Editor Controller(-esque)
 * @param {jQuery} $
 * @param {object} settings
 * @constructor
 */
var ILabImageEdit=function($, settings){
    var self=this;

    this.previewTimeout=null;
    this.previewsSuspended=false;
    this.parameters=[];

    this.settings=settings;

    this.modalContainer=$('#ilabm-container-'+settings.modal_id);
    this.editorArea = this.modalContainer.find('.ilabm-editor-area');
    this.waitModal=this.modalContainer.find('.ilabm-preview-wait-modal');
    this.previewImage=this.modalContainer.find('.imgix-preview-image');

    this.presets=new ILabImgixPresets($,this,this.modalContainer);

    this.focalPointEditor = new ILabFocalPointEditor($, this);
    this.faceEditor= new ILabFaceEditor($, this);

    this.modalContainer.find('.imgix-button-reset-all').on('click',function(){
        self.resetAll();
    });
    this.modalContainer.find('.imgix-button-save-adjustments').on('click',function(){
        self.apply();
    });

    this.modalContainer.find('.imgix-parameter').each(function(){
        var container=$(this);
        var type=container.data('param-type');
        if (type=='slider')
            self.parameters.push(new ImgixComponents.ImgixSlider(self,container));
        else if ((type=='color') || (type=='blend-color'))
            self.parameters.push(new ImgixComponents.ImgixColor(self,container));
        else if (type=='pillbox')
            self.parameters.push(new ImgixComponents.ImgixPillbox(self,container));
        else if (type=='media-chooser')
            self.parameters.push(new ImgixComponents.ImgixMediaChooser(self,container));
        else if (type=='alignment')
            self.parameters.push(new ImgixComponents.ImgixAlignment(self,container));
    });

    this.modalContainer.on('click','.imgix-pill',function(){
        var paramName=$(this).data('param');
        var param=self.modalContainer.find('#imgix-param-'+paramName);
        if (param.val()==1)
        {
            param.val(0);
            $(this).removeClass('pill-selected');
        }
        else
        {
            param.val(1);
            $(this).addClass('pill-selected');
        }

        self.preview();
    });

    this.modalContainer.find('.ilabm-editor-tabs').ilabTabs({
        currentValue: self.settings.size,
        tabSelected:function(tab){
            ILabModal.loadURL(tab.data('url'),true,function(response){
                self.bindUI(response);
            });
        }
    });

    this.modalContainer.find(".ilabm-sidebar-tabs").ilabSidebarTabs({
        delegate: this,
        container: this.modalContainer
    });

    /**
     * Performs the wordpress ajax post
     * @param action
     * @param data
     * @param callback
     * @private
     */
    this.postAjax=function(action,data,callback){
        var postData={};
        self.parameters.forEach(function(value,index){
            postData=value.saveValue(postData);
        });

        postData = this.focalPointEditor.save(postData);
        postData = this.faceEditor.save(postData);

        // console.log(postData);

        data['image_id'] = self.settings.image_id;
        data['action'] = action;
        data['size'] = self.settings.size;
        data['settings']=postData;

        $.post(ajaxurl, data, callback);
    };

    /**
     * Performs the actual request for a preview to be generated
     * @private
     */
    function _preview(){
        self.displayStatus('Building preview ...');

        self.waitModal.removeClass('is-hidden');

        self.postAjax('ilab_dynamic_images_preview',{},function(response) {
            if (response.status=='ok')
            {
                var sameSrc = (response.src == self.previewImage.attr('src'));
                var didLoad = false;

                self.previewImage.on('load',function(){
                    didLoad = true;
                    self.waitModal.addClass('is-hidden');
                    self.hideStatus();
                });

                self.previewImage.on('error', function(){
                    didLoad = true;
                    self.waitModal.addClass('is-hidden');
                    self.hideStatus();
                });

                self.previewImage.attr('src',response.src);

                if (sameSrc) {
                    setTimeout(function(){
                        if (!didLoad) {
                            self.waitModal.addClass('is-hidden');
                            self.hideStatus();
                        }
                    }, 3000);
                }
            }
            else
            {
                self.waitModal.addClass('is-hidden');
                self.hideStatus();
            }
        });
    }

    /**
     * Requests a preview to be generated.
     */
    this.preview=function(){
        if (self.previewsSuspended)
            return;

        ILabModal.makeDirty();

        clearTimeout(self.previewTimeout);
        self.previewTimeout=setTimeout(_preview,500);
    };

    /**
     * Binds the UI to the json response when selecting a tab or changing a preset
     * @param data
     */
    this.bindUI=function(data){
        if (data.hasOwnProperty('currentPreset') && (data.currentPreset!=null) && (data.currentPreset!='')) {
            var p=self.settings.presets[data.currentPreset];
            self.presets.setCurrentPreset(data.currentPreset,(p.default_for==data.size));
        }
        else
            self.presets.clearSelected();

        self.previewsSuspended=true;
        self.settings.size=data.size;
        self.settings.settings=data.settings;

        var rebind=function(){
            self.previewImage.off('load',rebind);
            self.parameters.forEach(function(value,index){
                value.reset(data.settings);
            });

            self.previewsSuspended=false;
            ILabModal.makeClean();
            self.buildFocalPoint();
        };

        if (data.src)
        {
            self.previewImage.on('load',rebind);
            self.previewImage.attr('src',data.src);
        }
        else
            rebind();
    };

    this.bindPreset=function(preset){
        self.previewsSuspended=true;
        self.settings.settings=preset.settings;

        self.previewImage.off('load');
        self.parameters.forEach(function(value,index){
            value.reset(self.settings.settings);
        });

        self.previewsSuspended=false;
        self.preview();
    };

    this.apply=function(){
        self.displayStatus('Saving adjustments ...');

        self.postAjax('ilab_dynamic_images_save', {}, function(response) {
            self.hideStatus();
            ILabModal.makeClean();

            alert("Adjustments have been saved.");
        });
    };

    /**
     * Reset all of the values
     */
    this.resetAll=function(){
        self.parameters.forEach(function(value,index){
            value.reset();
        });
    };

    this.displayStatus=function(message){
        self.modalContainer.find('.ilabm-status-label').text(message);
        self.modalContainer.find('.ilabm-status-container').removeClass('is-hidden');
    };

    this.hideStatus=function(){
        self.modalContainer.find('.ilabm-status-container').addClass('is-hidden');
    };


    $(document).on('edges-selected', function(e){
        $(document).trigger('change-entropy', [false]);
        this.faceEditor.disable();
        this.focalPointEditor.disable();
    }.bind(this));


    $(document).on('entropy-selected', function(e){
        $(document).trigger('change-edges', [false]);
        this.faceEditor.disable();
        this.focalPointEditor.disable();
    }.bind(this));
};


//# sourceMappingURL=ilab-media-tools.js.map
