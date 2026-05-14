<template>
  <div class="shop-page">
    <div class="shop-hero py-5">
      <div class="container-xl">
        <p class="section-eyebrow">— Our Collection</p>
        <h1 class="section-title">All Fragrances</h1>
      </div>
    </div>

    <div class="container-xl pb-5">
      <div class="row g-5">


        <div class="col-12 col-lg-3">
          <div class="shop-sidebar">


            <button class="filter-toggle d-lg-none w-100 mb-3" @click="filtersOpen = !filtersOpen">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
              {{ filtersOpen ? 'Hide Filters' : 'Show Filters' }}
            </button>

            <div class="sidebar-body" :class="{ 'open': filtersOpen }">


              <div class="filter-group">
                <h5 class="filter-label">Search</h5>
                <div class="search-input-wrap">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                  <input type="text" v-model="search" placeholder="Search fragrances…" class="filter-search" />
                </div>
              </div>


              <div class="filter-group">
                <h5 class="filter-label">Fragrance Family</h5>
                <div class="d-flex flex-column gap-2">
                  <label class="filter-check" v-for="family in families" :key="family">
                    <input type="checkbox" :value="family" v-model="selectedFamilies" />
                    <span>{{ family }}</span>
                  </label>
                </div>
              </div>


              <div class="filter-group">
                <h5 class="filter-label">Max Price</h5>
                <input type="range" class="price-range w-100" min="100" max="300" step="5" v-model.number="maxPrice" />
                <div class="d-flex justify-content-between mt-1">
                  <small>€100</small>
                  <small class="price-val">Up to €{{ maxPrice }}</small>
                </div>
              </div>


              <div class="filter-group">
                <h5 class="filter-label">Sort By</h5>
                <select class="filter-select w-100" v-model="sortBy">
                  <option value="default">Featured</option>
                  <option value="price-asc">Price: Low to High</option>
                  <option value="price-desc">Price: High to Low</option>
                  <option value="name">Name A–Z</option>
                </select>
              </div>


              <button class="reset-btn w-100 mt-2" @click="resetFilters" v-if="isFiltered">
                Clear all filters
              </button>

            </div>
          </div>
        </div>


        <div class="col-12 col-lg-9">


          <div class="d-flex justify-content-between align-items-center mb-4">
            <p class="results-count mb-0">{{ filteredProducts.length }} {{ filteredProducts.length === 1 ? 'fragrance' : 'fragrances' }}</p>
          </div>


          <div class="empty-results text-center py-5" v-if="filteredProducts.length === 0">
            <span class="empty-icon">🌿</span>
            <p class="mt-3">No fragrances match your filters.</p>
            <button class="reset-btn mt-2" @click="resetFilters">Clear filters</button>
          </div>

          <div class="row g-4" v-else>
            <div
              class="col-12 col-sm-6 col-xl-4"
              v-for="(product, i) in filteredProducts"
              :key="product.id"
              :style="{ animationDelay: i * 0.07 + 's' }"
            >
              <div class="product-card h-100 d-flex flex-column" @mouseenter="onProductView(product)">
                <div class="product-img" :style="{ background: product.bgColor }">

                  <button
                    class="wishlist-toggle"
                    :class="{ active: isWished(product.id) }"
                    :aria-label="isWished(product.id) ? 'Remove from wishlist' : 'Save to wishlist'"
                    @click.stop="$emit('wishlist-toggle', product)"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24"
                         :fill="isWished(product.id) ? 'currentColor' : 'none'"
                         stroke="currentColor" stroke-width="1.8">
                      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                  </button>
                  <div class="p-bottle">
                    <div class="pb-cap" :style="{ background: product.capColor }"></div>
                    <div class="pb-body" :style="{ background: product.bottleColor }">
                      <div class="pb-liquid" :style="{ background: product.liquidColor }"></div>
                      <div class="pb-label"><span>{{ product.shortName }}</span></div>
                    </div>
                  </div>
                  <div class="product-tags-overlay">
                    <span v-for="note in product.notes" :key="note">{{ note }}</span>
                  </div>
                </div>
                <div class="product-info p-4 d-flex flex-column flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <h3 class="product-name mb-0">{{ product.name }}</h3>
                      <p class="product-family mb-0">{{ product.family }}</p>
                    </div>
                    <div class="product-rating" v-if="product.rating">★ {{ product.rating }}</div>
                  </div>
                  <p class="product-desc flex-grow-1">{{ product.desc }}</p>
                  <div class="d-flex justify-content-between align-items-center mt-auto">
                    <div>
                      <span class="price-main">€{{ product.price }}</span>
                      <span class="price-vol">/ {{ product.volume }}</span>
                    </div>
                    <button class="add-btn d-flex align-items-center gap-1" @click="$emit('add-to-cart', product)">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                      Add
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import analytics from "../analytics.js";

export default {
  name: 'ShopPage',
  emits: ['add-to-cart', 'wishlist-toggle'],
  props: {
    products: {
      type: Array,
      required: true,
    },
    wishlistIds: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      search: '',
      selectedFamilies: [],
      maxPrice: 300,
      sortBy: 'default',
      filtersOpen: false,
      viewedProductIds: new Set(),
    };
  },
  computed: {
    families() {
      return [...new Set(this.products.map(p => p.family))];
    },
    isFiltered() {
      return this.search !== '' || this.selectedFamilies.length > 0 || this.maxPrice < 300 || this.sortBy !== 'default';
    },
    filteredProducts() {
      let list = [...this.products];

      if (this.search.trim()) {
        const q = this.search.toLowerCase();
        list = list.filter(p =>
          p.name.toLowerCase().includes(q) ||
          p.family.toLowerCase().includes(q) ||
          p.desc.toLowerCase().includes(q)
        );
      }

      if (this.selectedFamilies.length) {
        list = list.filter(p => this.selectedFamilies.includes(p.family));
      }

      list = list.filter(p => p.price <= this.maxPrice);

      if (this.sortBy === 'price-asc')  list.sort((a, b) => a.price - b.price);
      if (this.sortBy === 'price-desc') list.sort((a, b) => b.price - a.price);
      if (this.sortBy === 'name')       list.sort((a, b) => a.name.localeCompare(b.name));

      return list;
    },
  },
  methods: {
    resetFilters() {
      this.search = '';
      this.selectedFamilies = [];
      this.maxPrice = 300;
      this.sortBy = 'default';
    },
    isWished(productId) {
      return this.wishlistIds.includes(productId);
    },
    onProductView(product) {
      if (this.viewedProductIds.has(product.id)) return;
      this.viewedProductIds.add(product.id);
      analytics.trackPageview(
        "/shop/" + product.id,
        { name: product.name, family: product.family },
        product.id
      );
    },
  },
};
</script>

<style>

.product-img { position: relative; }
.wishlist-toggle {
  position: absolute;
  top: 12px; right: 12px;
  width: 36px; height: 36px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.6);
  background: rgba(255, 255, 255, 0.55);
  color: var(--text-light);
  display: inline-flex;
  align-items: center; justify-content: center;
  cursor: pointer;
  backdrop-filter: blur(2px);
  transition: background 0.2s, color 0.2s, transform 0.15s;
  z-index: 2;
}
.wishlist-toggle:hover {
  background: rgba(255, 255, 255, 0.85);
  color: var(--dusty-rose);
  transform: scale(1.05);
}
.wishlist-toggle.active {
  color: var(--dusty-rose);
  background: rgba(255, 255, 255, 0.9);
}
</style>