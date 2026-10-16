<template>
  <div class="overview-line">
    <md-card class="resync-banner">
      <md-card-content>
        <div class="resync-row">
          <div class="resync-header">
            <md-icon class="resync-icon">warning</md-icon>
            <span>
              Didn't sync cleanly ({{ attempts }}/{{ maxAttempts }} failed
              attempts).
            </span>
          </div>
          <md-button
            class="md-raised md-dense"
            :disabled="syncing"
            @click="$emit('resync')"
          >
            Resync now
          </md-button>
        </div>
      </md-card-content>
    </md-card>
  </div>
</template>

<script>
export default {
  name: "ResyncBanner",
  props: {
    attempts: {
      type: Number,
      required: true,
    },
    maxAttempts: {
      type: Number,
      required: true,
    },
    syncing: {
      type: Boolean,
      default: false,
    },
  },
}
</script>

<style scoped lang="scss">
.overview-line {
  margin-bottom: 1rem;
}

.resync-banner {
  border-left: 4px solid #f9a825;
}

.resync-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.resync-icon {
  color: #f9a825;
}

.resync-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
