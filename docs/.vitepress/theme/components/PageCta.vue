<template>
  <div class="page-cta">
    <div class="page-cta-content">
      <h2>{{ title }}</h2>
      <p>{{ description }}</p>
      <div class="page-cta-actions">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  description: string
}>()
</script>

<style scoped lang="scss">
.page-cta {
  margin: 4rem 0;
  padding: 4rem 2rem;
  background: linear-gradient(
    135deg,
    var(--vp-c-brand-1) 0%,
    var(--vp-c-brand-2) 100%
  );
  border-radius: 1.5rem;
  text-align: center;
}

.page-cta-content {
  max-width: 600px;
  margin: 0 auto;

  h2 {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin: 0 0 1rem;
  }

  p {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
    margin: 0 0 2rem;
  }
}

.page-cta-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.page-cta-actions :deep(.cta-btn) {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1.75rem;
  border-radius: 0.75rem;
  font-weight: 600;
  font-size: 1rem;
  text-decoration: none;
  transition: all 0.2s ease;
  background: white;
  color: var(--vp-c-brand-1);

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  }
}

.page-cta-actions :deep(.cta-btn-outline) {
  background: transparent;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.5);

  &:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
  }
}
</style>
