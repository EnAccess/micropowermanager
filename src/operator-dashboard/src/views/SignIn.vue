<template>
  <div class="signin">
    <form class="signin__card" @submit.prevent="submit">
      <h1 class="signin__title">MPM Operations</h1>
      <p class="signin__subtitle">{{ $tc("phrases.signInSubtitle") }}</p>

      <label class="signin__label" for="operator-username">
        {{ $tc("words.username") }}
      </label>
      <input
        id="operator-username"
        v-model="username"
        class="signin__input"
        type="text"
        autocomplete="username"
        required
      />

      <label class="signin__label" for="operator-password">
        {{ $tc("words.password") }}
      </label>
      <input
        id="operator-password"
        v-model="password"
        class="signin__input"
        type="password"
        autocomplete="current-password"
        required
      />

      <p v-if="error" class="signin__error">{{ $tc(error) }}</p>

      <button class="signin__submit" type="submit" :disabled="submitting">
        {{ $tc("phrases.signIn") }}
      </button>
    </form>
  </div>
</template>

<script>
export default {
  name: "SignIn",
  data() {
    return {
      username: "",
      password: "",
      submitting: false,
    }
  },
  computed: {
    error() {
      return this.$store.getters["session/error"]
    },
  },
  methods: {
    async submit() {
      this.submitting = true
      try {
        await this.$store.dispatch("session/signIn", {
          username: this.username,
          password: this.password,
        })
      } catch {
        // The store records the reason; it renders through `error` above.
      } finally {
        this.submitting = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.signin {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: $brand-background;
  padding: 24px;
}

.signin__card {
  width: 100%;
  max-width: 360px;
  background: $brand-white;
  border: 1px solid $ops-card-border;
  border-radius: $ops-radius-card;
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
}

.signin__title {
  margin: 0;
  font-size: 22px;
  font-weight: 300;
  color: $brand-primary-dark;
}

.signin__subtitle {
  margin: 4px 0 20px;
  font-size: 13px;
  font-weight: 300;
  color: $ops-text-muted;
}

.signin__label {
  font-size: 12px;
  font-weight: 300;
  color: $ops-text-muted;
  margin-bottom: 4px;
}

.signin__input {
  background: $brand-background;
  border: 1px solid $ops-card-border;
  border-radius: $ops-radius-control;
  padding: 9px 12px;
  font-family: inherit;
  font-size: 13.5px;
  color: $ops-text;
  margin-bottom: 14px;

  &:focus {
    outline: none;
    border-color: $brand-primary-light;
  }
}

.signin__error {
  margin: 0 0 12px;
  font-size: 12.5px;
  color: $ops-text-watch;
}

.signin__submit {
  border: none;
  border-radius: $ops-radius-control;
  background: $brand-primary;
  color: $brand-white;
  font-family: inherit;
  font-size: 13.5px;
  font-weight: 500;
  padding: 10px 14px;
  cursor: pointer;

  &:disabled {
    opacity: 0.6;
    cursor: default;
  }
}
</style>
