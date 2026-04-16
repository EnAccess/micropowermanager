<template>
  <div class="page-hero">
    <div class="page-hero-content">
      <span v-if="badge" class="page-hero-badge">{{ badge }}</span>
      <h1 class="page-hero-title">{{ title }}</h1>
      <p class="page-hero-tagline">{{ tagline }}</p>
      <div class="page-hero-actions">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  tagline: string
  badge?: string
}>()
</script>

<style scoped lang="scss">
$tablet: 719px;

.page-hero {
  position: relative;
  padding: 4rem 0;
  text-align: center;
  background: linear-gradient(
    135deg,
    rgba(27, 117, 186, 0.08) 0%,
    rgba(119, 217, 247, 0.08) 50%,
    rgba(23, 69, 105, 0.08) 100%
  );
  border-radius: 1.5rem;
  margin: 2rem 0;
  overflow: hidden;

  &::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
      radial-gradient(circle at 20% 80%, rgba(27, 117, 186, 0.1) 0%, transparent 50%),
      radial-gradient(circle at 80% 20%, rgba(119, 217, 247, 0.1) 0%, transparent 50%);
    pointer-events: none;
  }
}

.page-hero-content {
  position: relative;
  z-index: 1;
}

.page-hero-badge {
  display: inline-block;
  padding: 0.375rem 1rem;
  border-radius: 2rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: rgba(27, 117, 186, 0.15);
  color: var(--vp-c-brand-1);
  margin-bottom: 1.5rem;
}

.page-hero-title {
  font-size: 3rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--vp-c-brand-1), var(--vp-c-brand-4));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 1rem;
  letter-spacing: -0.02em;
  line-height: 1.2;
  padding-bottom: 0.1em;

  @media (max-width: $tablet) {
    font-size: 2.25rem;
  }
}

.page-hero-tagline {
  font-size: 1.25rem;
  color: var(--vp-c-text-2);
  max-width: 640px;
  margin: 0 auto 2rem;
  line-height: 1.6;

  @media (max-width: $tablet) {
    font-size: 1rem;
    padding: 0 1rem;
  }
}

.page-hero-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.page-hero-actions :deep(.hero-btn) {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 0.75rem;
  font-weight: 600;
  font-size: 0.95rem;
  text-decoration: none;
  transition: all 0.2s ease;
}

.page-hero-actions :deep(.hero-btn-primary) {
  background: var(--vp-c-brand-1);
  color: white;

  &:hover {
    background: var(--vp-c-brand-2);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(27, 117, 186, 0.3);
  }
}

.page-hero-actions :deep(.hero-btn-secondary) {
  background: var(--vp-c-bg-soft);
  color: var(--vp-c-text-1);
  border: 1px solid var(--vp-c-divider);

  &:hover {
    background: var(--vp-c-bg-alt);
    border-color: var(--vp-c-brand-1);
    color: var(--vp-c-brand-1);
  }
}
</style>
