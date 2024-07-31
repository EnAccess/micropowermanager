<template>
  <div v-if="frontmatter.highlights">
    <div>
      <div
        class="vp-highlight-wrapper"
        v-for="highlight in frontmatter.highlights"
      >
        <div class="vp-highlight">
          <img
            class="vp-highlight-image"
            :src="highlight.image"
            :alt="highlight.header"
          />
          <div class="vp-highlight-info-wrapper">
            <div class="vp-highlight-info">
              <h3 class="vp-highlight-header">{{ highlight.header }}</h3>
              <p class="vp-highlight-description">
                {{ highlight.description }}
              </p>
              <ul class="vp-highlights">
                <li
                  class="vp-highlight-item-wrapper"
                  v-for="highlightItem in highlight.highlightItems"
                >
                  <div class="vp-highlight-item">
                    <img
                      :src="highlightItem.icon"
                      :alt="highlightItem.title"
                      class="vp-highlight-icon"
                    />
                    <span class="vp-highlight-title">
                      {{ highlightItem.title }}
                    </span>
                    <span class="vp-highlight-title-spacer"></span>
                    <span class="vp-highlight-details">
                      {{ highlightItem.details }}
                    </span>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useData } from "vitepress"

const { frontmatter } = useData()
</script>

<style scoped lang="scss">
$pc: 1440px;
$laptop: 1280px;
$pad: 959px;
$tablet: 719px;
$mobile: 419px;

.vp-highlight-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;

  &:nth-child(even) .vp-highlight {
    flex-flow: row-reverse;
    background-image: linear-gradient(
      180deg,
      var(--vp-c-bg-soft),
      var(--vp-c-bg-soft)
    );
  }
}

.vp-highlight {
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  max-width: var(--home-page-width);
  margin: 0 auto;
  padding: 1.5rem 2.5rem;

  @media (max-width: $tablet) {
    display: block;
    padding: 1.5rem;
    text-align: center;
  }
}

.vp-highlight-image {
  width: 24rem;
  margin: 2rem 4rem;

  @media (max-width: $pad) {
    width: 12rem;
  }

  @media (max-width: $tablet) {
    width: 8rem;
    margin: 0 auto;
  }
}

.vp-highlight-info {
  text-align: start;
  padding: 2rem;

  @media (max-width: $tablet) {
    padding: 1rem 0;
  }
}

.vp-highlight-header {
  margin-bottom: 1.5rem;
  font-size: 36px;
  color: var(--vp-c-brand-1);

  @media (max-width: $tablet) {
    font-size: 2.25rem;
    text-align: center;
  }

  @media (max-width: $mobile) {
    font-size: 2rem;
  }
}

.vp-highlight-description,
.vp-highlight-details {
  font-size: 14px;
  color: var(--vp-c-text-1);
}

.vp-highlights {
  margin: 0;
  padding: 0;
  list-style: none;
}

.vp-highlight-item-wrapper {
  position: relative;
  padding: 0.5em 0.5em 0.5em 1.75em;
  border-radius: 0.5rem;
  list-style: none;
  margin-bottom: 0.5em;
}

.vp-highlight-icon {
  position: absolute;
  left: 0;
  top: 0;
  width: 2em;
  height: 2em;
  margin-top: 0.5em;
  margin-right: 0.5em;
}

.vp-highlight-item {
  position: relative;
  padding-left: 3em;
  margin-bottom: 0.5em;
}

.vp-highlight-title {
  font-weight: 600;
  font-size: 14px;
  margin: 0;
}

.vp-highlight-details {
  margin-top: 0.5rem;
}

.vp-highlight-title-spacer {
  margin-right: 3px;
}
</style>
