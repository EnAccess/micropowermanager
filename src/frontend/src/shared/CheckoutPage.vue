<template>
  <div class="checkout-page">
    <div class="checkout-page__inner">
      <header class="text-center">
        <img
          class="checkout-page__logo"
          :src="mpmLogo"
          alt="MicroPowerManager"
          width="116"
          height="60"
        />
        <div class="md-title">MicroPowerManager</div>
        <div class="md-caption">
          {{ subtitle }}
          <span v-if="companyName">&nbsp;·&nbsp;{{ companyName }}</span>
        </div>
      </header>
      <md-card>
        <md-card-content>
          <slot></slot>
        </md-card-content>
      </md-card>
    </div>
  </div>
</template>

<script>
import mpmLogo from "@/assets/images/mpmlogo_raw.png"

export default {
  name: "CheckoutPage",
  props: {
    subtitle: {
      type: String,
      required: true,
    },
    companyName: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      mpmLogo,
    }
  },
}
</script>

<style scoped lang="scss">
// Standalone shell for public (unauthenticated) payment pages, which render
// outside the dashboard layout and have no sidebar or navbar.
.checkout-page {
  min-height: 100vh;
  padding: 3rem 1.25rem;
  background-color: $brand-background;
}

.checkout-page__inner {
  width: 100%;
  max-width: 440px;
  margin: 0 auto;
}

// The img width/height attributes reserve the box before the asset loads
// (prevents a layout shift); this rule only centers it.
.checkout-page__logo {
  display: block;
  margin: 0 auto 0.5rem;
}
</style>
