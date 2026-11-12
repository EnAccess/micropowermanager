<template>
  <div class="plugin-showcase">
    <div
      v-for="category in categories"
      :key="category.title"
      class="plugin-showcase-category"
    >
      <h3 class="plugin-showcase-title">
        {{ category.title }}
        <span class="plugin-showcase-count">{{ category.plugins.length }}</span>
      </h3>
      <ul class="plugin-showcase-plugins">
        <li v-for="plugin in category.plugins" :key="plugin.url">
          <a class="plugin-showcase-plugin" :href="withBase(plugin.url)">
            {{ plugin.title }}
          </a>
        </li>
      </ul>
    </div>

    <a class="plugin-showcase-all" :href="withBase(overviewLink)">
      Browse all plugins →
    </a>
  </div>
</template>

<script setup lang="ts">
import { withBase } from "vitepress"
import { data } from "../plugins.data.mts"

defineProps<{ overviewLink: string }>()

const categories = data
  .map((category) => ({
    ...category,
    plugins: category.plugins.filter((plugin) => plugin.isReady),
  }))
  .filter((category) => category.plugins.length > 0)
</script>

<style scoped lang="scss">
.plugin-showcase {
  display: grid;
  gap: 1rem;
  width: 100%;
}

.plugin-showcase-category {
  padding: 1.25rem;
  border: 1px solid var(--vp-c-divider);
  border-radius: 12px;
  background-color: var(--vp-c-bg);
  box-shadow: var(--vp-shadow-1);
}

.plugin-showcase-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin: 0 0 0.875rem;
  font-size: 15px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--vp-c-text-1);
}

.plugin-showcase-count {
  flex-shrink: 0;
  min-width: 24px;
  padding: 0 8px;
  border-radius: 12px;
  background-color: color-mix(in srgb, var(--vp-c-brand-1) 10%, transparent);
  font-size: 12px;
  line-height: 24px;
  text-align: center;
  color: var(--vp-c-brand-1);
}

.plugin-showcase-plugins {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.plugin-showcase-plugin {
  display: block;
  padding: 4px 12px;
  border: 1px solid var(--vp-c-divider);
  border-radius: 999px;
  background-color: var(--vp-c-bg-soft);
  font-size: 13px;
  font-weight: 500;
  line-height: 1.5;
  color: var(--vp-c-text-1);
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    color 0.2s ease;

  &:hover {
    border-color: var(--vp-c-brand-1);
    color: var(--vp-c-brand-1);
  }
}

.plugin-showcase-all {
  justify-self: start;
  padding: 10px 20px;
  border-radius: 999px;
  background-color: var(--vp-button-brand-bg);
  font-size: 14px;
  font-weight: 600;
  color: var(--vp-button-brand-text);
  text-decoration: none;
  transition: background-color 0.2s ease;

  &:hover {
    background-color: var(--vp-button-brand-hover-bg);
  }
}
</style>
