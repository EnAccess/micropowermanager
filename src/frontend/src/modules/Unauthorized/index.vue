<template>
  <div class="unauthorized-page">
    <img
      class="unauthorized-illustration"
      src="@/assets/images/unauthorized.png"
      alt="Unauthorized"
    />
    <h1>{{ $tc("words.unauthorized") }}</h1>
    <p>
      {{ $tc("phrases.unauthorizedDescription") }}
    </p>
    <md-button class="md-primary md-raised" @click="$router.push('/')">
      {{ $tc("phrases.goBackHome") }}
    </md-button>
  </div>
</template>

<script>
export default {
  name: "UnauthorizedPage",
}
</script>

<style scoped>
.unauthorized-page {
  min-height: 60vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 1rem;
  padding: 2rem;
}

.unauthorized-illustration {
  width: 220px;
  max-width: 80%;
}
</style>
