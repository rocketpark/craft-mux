export default class DataBinding {
    constructor({ object, property, element, attribute = 'innerText' }) {
        this.object = object;
        this.property = property;
        this.element = element;
        this.attribute = attribute;

        this.pendingUpdates = new Set();
        this.memoizedSetter = memoize((val) => { this.object[this.property] = val; });

        this.initialize();
    }

    initialize() {
        this.setupProxy();  // Set up Proxy to monitor changes in the model
        this.render();      // Initial render to bind UI to the model
    }

    setupProxy() {
        // Proxy to monitor changes in the model and trigger UI updates
        const handler = {
            set: (obj, prop, value) => {
                if (prop === this.property) {
                    obj[prop] = value;
                    this.debouncedBatchUpdates(); // Trigger re-render after updates
                }
                return true;
            },
        };

        this.proxyObject = new Proxy(this.object, handler); // Assign Proxy to the object
    }

    render = () => {
        let val = this.object[this.property]; // Retrieve the value from the object property
        if (val === undefined) {
            val = "";
        }
        this.element[this.attribute] = val; // Update the specified attribute of the element
    };

    debouncedBatchUpdates = debounce(() => {
        this.pendingUpdates.forEach(update => update());
        this.pendingUpdates.clear();
        this.render(); // Re-render UI after updates
    }, 16); // roughly 60fps

    unbind() {
        this.pendingUpdates.clear();
        this.memoizedSetter = null;
    }
}

// Utility functions
const memoize = (fn) => {
    const cache = new WeakMap();
    return function (...args) {
        const key = args[0];
        if (!cache.has(key)) {
            cache.set(key, fn.apply(this, args));
        }
        return cache.get(key);
    };
};

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};