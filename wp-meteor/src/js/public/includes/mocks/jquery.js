import delta from "../utils/delta";
import {
  addEventListener,
  DCL
} from "../literals";
import {
  d,
  c,
  ce
} from "../globals";
let mocked = true;
export default class jQueryMock {
  constructor() {
    this.known = [];
  }
  init() {
    let Mock;
    let Mock$;
    const override = (jQuery, symbol) => {
      if (mocked && jQuery && jQuery.fn && !jQuery.__wpmeteor) {
        process.env.DEBUG && c(delta(), "new " + symbol + " detected", jQuery.__wpmeteor, jQuery);
        const enqueue = function(func) {
          process.env.DEBUG && c(delta(), "enqueued jQuery(func)", func);
          if (func === jQuery) {
            return this;
          }
          d[addEventListener](DCL, (e) => {
            process.env.DEBUG && c(delta(), "running enqueued jQuery function", func);
            func.call(d, jQuery, e, "jQueryMock");
          });
          return this;
        };
        this.known.push([jQuery, jQuery.fn.ready, jQuery.fn.init?.prototype?.ready]);
        jQuery.fn.ready = enqueue;
        if (jQuery.fn.init?.prototype?.ready) {
          jQuery.fn.init.prototype.ready = enqueue;
        }
        jQuery.__wpmeteor = true;
      }
      return jQuery;
    };
    if (window.jQuery || window.$) {
      process.env.DEBUG && ce(delta(), "WARNING: JQUERY WAS INSTALLED BEFORE WP-METEOR, PROBABLY FROM A CHROME EXTENSION");
    }
    Object.defineProperty(window, "jQuery", {
      get() {
        return Mock;
      },
      set(jQuery) {
        Mock = override(jQuery, "jQuery");
      },
      configurable: true
    });
    Object.defineProperty(window, "$", {
      get() {
        return Mock$;
      },
      set($) {
        Mock$ = override($, "$");
      },
      configurable: true
    });
  }
  unmock() {
    this.known.forEach(([jQuery, oldReady, oldPrototypeReady]) => {
      process.env.DEBUG && c(delta(), "unmocking jQuery", jQuery);
      jQuery.fn.ready = oldReady;
      if (jQuery.fn.init?.prototype?.ready && oldPrototypeReady) {
        jQuery.fn.init.prototype.ready = oldPrototypeReady;
      }
    });
    mocked = false;
  }
}
//# sourceMappingURL=jquery.js.map
