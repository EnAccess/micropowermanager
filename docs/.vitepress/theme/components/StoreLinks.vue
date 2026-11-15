<template>
  <div class="vp-store-links">
    <a
      v-for="store in stores"
      :key="store.store"
      :href="store.link"
      target="_blank"
      rel="noopener noreferrer"
    >
      <img
        :src="storeBadges[store.store].src"
        :alt="storeBadges[store.store].alt"
      />
    </a>
  </div>
</template>

<script setup lang="ts">
import { storeBadges, fieldAppStores, type StoreLink } from "../storeLinks"

withDefaults(defineProps<{ stores?: StoreLink[] }>(), {
  stores: () => fieldAppStores,
})
</script>

<style scoped lang="scss">
.vp-store-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;

  a {
    transition: transform 0.2s ease;

    &:hover {
      transform: translateY(-2px);
    }
  }

  img {
    display: block;
    height: 44px;
  }
}
</style>
