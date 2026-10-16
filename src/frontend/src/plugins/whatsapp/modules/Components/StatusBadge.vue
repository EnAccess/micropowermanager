<template>
  <span class="status-badge" :class="statusClass">
    {{ status }}
  </span>
</template>

<script>
export default {
  name: "StatusBadge",
  props: {
    status: {
      type: String,
      required: true,
    },
  },
  computed: {
    statusClass() {
      return "status-" + this.status.toLowerCase().replace(" ", "-")
    },
  },
}
</script>

<style lang="scss" scoped>
.status-badge {
  border-radius: 12px;
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 4px 10px;
}

.status-active,
.status-delivered,
.status-read,
.status-sent {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-draft,
.status-queued,
.status-sending {
  background: #fff8e1;
  color: #ef6c00;
}

.status-failed {
  background: #ffebee;
  color: #c62828;
}
</style>
