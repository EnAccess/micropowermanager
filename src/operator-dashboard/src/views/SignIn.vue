<template>
  <div class="signin">
    <div class="signin__column">
      <header class="signin__header">
        <h1 class="signin__title">MPM Operations</h1>
        <h5 class="signin__subtitle">{{ $tc("phrases.signInSubtitle") }}</h5>
        <div class="signin__divider"></div>
        <p v-if="error" class="signin__error">{{ $tc(error) }}</p>
      </header>

      <form class="signin__card" @submit.prevent="submit">
        <label class="mpm-field signin__field">
          <span class="mpm-field__label">{{ $tc("words.username") }}</span>
          <input
            v-model="username"
            class="mpm-field__input"
            type="text"
            autocomplete="username"
            required
          />
        </label>

        <label class="mpm-field signin__field">
          <span class="mpm-field__label">{{ $tc("words.password") }}</span>
          <input
            v-model="password"
            class="mpm-field__input"
            type="password"
            autocomplete="current-password"
            required
          />
        </label>

        <div class="signin__actions">
          <button
            class="mpm-button mpm-button--raised mpm-button--primary"
            type="submit"
            :disabled="submitting"
          >
            <span class="material-icons">login</span>
            {{ $tc("phrases.signIn") }}
          </button>
        </div>
      </form>
    </div>
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
// Mirrors src/frontend/src/modules/Login/LoginCard.vue.
.signin {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  background: linear-gradient(
    to right,
    $brand-background-dark,
    $brand-background
  );
}

.signin__column {
  width: 490px;
  max-width: 100%;
}

.signin__header {
  text-align: center;
}

.signin__title {
  margin: 0;
  padding: 1rem 1rem 0;
  font-size: x-large;
  font-weight: bold;
}

.signin__subtitle {
  margin: 5px 0 0;
  color: #8c8c8c;
  font-size: $font-caption;
}

.signin__divider {
  margin: 0.5rem 0 2rem;
  border-bottom: 2px solid #f9b839;
}

.signin__error {
  margin: -1rem 0 2rem;
  padding: 15px;
  background: #ac2925;
  color: $brand-white;
}

.signin__card {
  padding: 32px 16px 8px;
  background: $brand-white;
  border-radius: $ops-radius-card;
  box-shadow: $ops-shadow-card;
}

.signin__field {
  margin-bottom: 24px;
}

.signin__actions {
  display: flex;
  justify-content: flex-end;
  padding: 8px 0;
}
</style>
