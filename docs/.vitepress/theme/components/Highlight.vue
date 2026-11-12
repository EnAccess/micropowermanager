<template>
  <div v-if="frontmatter.highlights" ref="root">
    <section
      v-for="highlight in frontmatter.highlights"
      :id="highlight.anchor"
      :key="highlight.header"
      class="vp-highlight-band"
    >
      <div
        class="vp-highlight"
        :class="{ 'vp-highlight--phone': highlight.phoneScreen }"
      >
        <div class="vp-highlight-visual">
          <div v-if="highlight.phoneScreen" class="vp-highlight-phone">
            <img
              :src="highlight.phoneScreen"
              :alt="`${highlight.header} screenshot`"
            />
          </div>
          <PluginShowcase
            v-else-if="highlight.pluginShowcase"
            :overview-link="highlight.pluginShowcase.overviewLink"
          />
          <div v-else class="vp-highlight-window">
            <div class="vp-highlight-window-bar" aria-hidden="true">
              <span />
              <span />
              <span />
            </div>
            <img
              class="vp-highlight-image"
              :src="highlight.image"
              :alt="highlight.header"
            />
          </div>
        </div>

        <div class="vp-highlight-info">
          <p v-if="highlight.eyebrow" class="vp-highlight-eyebrow">
            {{ highlight.eyebrow }}
          </p>
          <h2 class="vp-highlight-header">{{ highlight.header }}</h2>
          <p class="vp-highlight-description">{{ highlight.description }}</p>

          <StoreLinks
            v-if="highlight.storeLinks"
            :stores="highlight.storeLinks"
          />

          <a
            v-if="highlight.learnMore"
            class="vp-highlight-learn-more"
            :href="highlight.learnMore.link"
          >
            {{ highlight.learnMore.text }} →
          </a>
        </div>

        <ul class="vp-highlight-items">
          <li
            v-for="highlightItem in highlight.highlightItems"
            :key="highlightItem.title"
            class="vp-highlight-item"
          >
            <span class="vp-highlight-icon">
              <img :src="highlightItem.icon" alt="" />
            </span>
            <div>
              <h3 class="vp-highlight-title">{{ highlightItem.title }}</h3>
              <p class="vp-highlight-details">{{ highlightItem.details }}</p>
              <a
                v-if="highlightItem.link"
                class="vp-highlight-learn-more vp-highlight-item-link"
                :href="highlightItem.link.link"
              >
                {{ highlightItem.link.text }} →
              </a>
            </div>
          </li>
        </ul>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue"
import { useData } from "vitepress"
import PluginShowcase from "./PluginShowcase.vue"
import StoreLinks from "./StoreLinks.vue"

const { frontmatter } = useData()

const root = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

onMounted(() => {
  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
  ).matches
  if (!root.value || prefersReducedMotion) return

  // Hiding is opt-in after mount so content stays visible during SSR and without JS.
  root.value.classList.add("reveal-enabled")
  observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue
        entry.target.classList.add("is-visible")
        observer?.unobserve(entry.target)
      }
    },
    { threshold: 0.15 },
  )
  root.value
    .querySelectorAll(".vp-highlight")
    .forEach((element) => observer?.observe(element))
})

onBeforeUnmount(() => observer?.disconnect())
</script>

<style scoped lang="scss">
$pad: 959px;
$tablet: 639px;

.vp-highlight-band {
  padding: 6rem 64px;

  &:nth-child(even) {
    background-color: var(--vp-c-bg-soft);
  }

  @media (max-width: $pad) {
    padding: 4rem 48px;
  }

  @media (max-width: $tablet) {
    padding: 3.5rem 24px;
  }
}

// The outer `1fr` rows centre the text block vertically against the taller visual.
.vp-highlight {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: 1fr auto auto 1fr;
  grid-template-areas:
    "visual ."
    "visual info"
    "visual items"
    "visual .";
  column-gap: 4rem;
  max-width: 1152px;
  margin: 0 auto;

  .vp-highlight-band:nth-child(even) & {
    grid-template-areas:
      ". visual"
      "info visual"
      "items visual"
      ". visual";
  }

  @media (max-width: $pad) {
    &,
    .vp-highlight-band:nth-child(even) & {
      grid-template-columns: 1fr;
      grid-template-rows: auto;
      grid-template-areas:
        "visual"
        "info"
        "items";
      row-gap: 2.5rem;
    }
  }
}

.reveal-enabled .vp-highlight {
  opacity: 0;
  transform: translateY(16px);
  transition:
    opacity 0.6s ease,
    transform 0.6s ease;

  &.is-visible {
    opacity: 1;
    transform: none;
  }
}

.vp-highlight-visual {
  grid-area: visual;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.vp-highlight-window {
  width: 100%;
  overflow: hidden;
  border: 1px solid var(--vp-c-divider);
  border-radius: 12px;
  background-color: var(--vp-c-bg);
  box-shadow: var(--vp-shadow-3);
}

.vp-highlight-window-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 36px;
  padding: 0 14px;
  border-bottom: 1px solid var(--vp-c-divider);
  background-color: var(--vp-c-bg-soft);

  span {
    width: 12px;
    height: 12px;
    border-radius: 50%;

    &:nth-child(1) {
      background-color: #ff5f57;
    }

    &:nth-child(2) {
      background-color: #febc2e;
    }

    &:nth-child(3) {
      background-color: #28c840;
    }
  }
}

.vp-highlight-image {
  display: block;
  width: 100%;
}

// The phone's height follows the screenshot so it is never cropped.
// `position: relative` paints it above the absolutely positioned disc behind it.
.vp-highlight-phone {
  position: relative;
  width: min(260px, 60%);
  padding: 10px;
  border-radius: 38px;
  background-color: #1b1b1f;
  box-shadow:
    0 0 0 3px var(--brand-secondary),
    0 24px 48px -24px rgba(0, 0, 0, 0.35);

  // The default bezel matches the dark theme's page background, so lift it there.
  .dark & {
    background-color: #3a3a40;
  }

  img {
    display: block;
    width: 100%;
    border-radius: 28px;
  }
}

.vp-highlight-info {
  grid-area: info;
}

.vp-highlight-eyebrow {
  margin: 0 0 0.75rem;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--vp-c-brand-1);
}

.vp-highlight-header {
  margin: 0;
  font-size: clamp(1.75rem, 1.2rem + 2vw, 2.25rem);
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.02em;
  color: var(--vp-c-text-1);
}

.vp-highlight-description {
  margin: 1rem 0 2rem;
  font-size: 16px;
  line-height: 1.7;
  color: var(--vp-c-text-2);
}

.vp-highlight-items {
  grid-area: items;
  display: grid;
  align-content: start;
  gap: 1.25rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.vp-highlight-item {
  display: grid;
  grid-template-columns: 40px 1fr;
  gap: 1rem;
  align-items: start;
}

// In the phone section the items float as cards over the phone's edges.
.vp-highlight--phone {
  .vp-highlight-items {
    gap: 0.75rem;
  }

  .vp-highlight-item {
    padding: 1rem 1.25rem;
    border: 1px solid var(--vp-c-divider);
    border-radius: 16px;
    background-color: var(--vp-c-bg);
    box-shadow: 0 12px 32px -16px rgba(0, 0, 0, 0.2);
  }

  .vp-highlight-visual::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100%;
    aspect-ratio: 1;
    border-radius: 50%;
    background-color: color-mix(in srgb, var(--vp-c-brand-4) 30%, transparent);
    transform: translate(-50%, -50%);

    .dark & {
      background-color: color-mix(
        in srgb,
        var(--vp-c-brand-4) 14%,
        transparent
      );
    }
  }

  // Cards hang into the column gap and page gutter so they only cover the phone's edge.
  @media (min-width: $pad + 1) {
    .vp-highlight-items {
      grid-area: visual;
      position: relative;
      z-index: 1;
      display: block;
    }

    .vp-highlight-item {
      position: absolute;
      width: 250px;

      // Positions are tuned to the current screenshot's empty or decorative areas.
      &:nth-child(1) {
        top: 4%;
        right: -1.5rem;
      }

      &:nth-child(2) {
        top: 54%;
        right: -1.5rem;
      }

      &:nth-child(3) {
        bottom: 14%;
        left: -2rem;
      }
    }
  }
}

.vp-highlight-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background-color: color-mix(in srgb, var(--vp-c-brand-1) 10%, transparent);

  img {
    width: 24px;
    height: 24px;
  }
}

.vp-highlight-title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  line-height: 1.5;
  color: var(--vp-c-text-1);
}

.vp-highlight-details {
  margin: 0.25rem 0 0;
  font-size: 14px;
  line-height: 1.6;
  color: var(--vp-c-text-2);
}

.vp-highlight-learn-more {
  display: inline-block;
  margin-top: 1.5rem;
  font-size: 14px;
  font-weight: 600;
  color: var(--vp-c-brand-1);
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

.vp-highlight-item-link {
  margin-top: 0.375rem;
}
</style>
