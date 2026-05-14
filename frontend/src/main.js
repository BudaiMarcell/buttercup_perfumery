import { createApp } from 'vue'
import App from './App.vue'

// Custom Bootstrap build — only the partials we use. See
// styles/bootstrap.scss for the exact list. Replaces the full
// `bootstrap.min.css` import (190 KB) with a much smaller bundle.
import './styles/bootstrap.scss'

// Bootstrap's JS bundle (collapse, dropdown, modal, etc.) is NOT
// imported on purpose. The only Bootstrap JS feature this app uses
// is the mobile navbar collapse, and that's handled by Vue's own
// `mobileMenuOpen` state in App.vue. Dropping the JS also drops the
// `@popperjs/core` transitive dep (~22 KB).

import router from './router'
import './style.css'

createApp(App).use(router).mount('#app')
