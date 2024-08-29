<template>
  <div class="center-container">
    <div class="centered-item">
      <img v-if="size !== 'sm'" src="@/assets/spinner/spinner.gif" alt="" />
      <img v-else src="@/assets/spinner/spinner_sm.gif" alt="" />
    </div>
  </div>
</template>

<script>
export default {
  name: "Loader",
  props: {
    size: {
      type: String,
      default: "md",
    },
  },
}
</script>

<style scoped>
.center-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 50vh; /* Optional: Adjust the height as needed to center vertically. */
}

.centered-item {
  /* Styles for your centered item */
}
</style>
