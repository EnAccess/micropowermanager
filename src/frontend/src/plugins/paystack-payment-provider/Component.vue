<template>
  <div>
    <widget color="green" :title="title">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-size-100"
          style="
            margin: auto;
            text-align: center;
            padding: 3rem;
            font-size: initial;
            font-weight: 500;
            display: grid;
          "
        >
          <span>
            Configure your Paystack payment provider to start accepting payments from customers.
          </span>
          <span style="margin-top: 16px">
            You'll need your Paystack API keys and webhook configuration.
          </span>
        </div>
        <div class="md-layout-item md-size-100" style="text-align: center">
          <div class="configuration-steps">
            <div class="step">
              <md-icon class="step-icon">vpn_key</md-icon>
              <h4>1. Get your Paystack API Keys</h4>
              <p>
                Log in to your 
                <a href="https://dashboard.paystack.com" target="_blank">Paystack Dashboard</a>
                and get your Secret Key and Public Key
              </p>
            </div>
            
            <div class="step">
              <md-icon class="step-icon">settings</md-icon>
              <h4>2. Configure Webhook</h4>
              <p>
                Set up webhook endpoints in your Paystack dashboard to receive payment notifications
              </p>
            </div>
            
            <div class="step">
              <md-icon class="step-icon">payment</md-icon>
              <h4>3. Test Integration</h4>
              <p>
                Use test credentials first to verify your integration before going live
              </p>
            </div>
          </div>
          
          <div class="buttons-area">
            <md-button
              role="button"
              class="md-raised md-primary"
              @click="goToConfiguration"
            >
              Configure Paystack Now
            </md-button>
            <md-button
              role="button"
              class="md-raised"
              @click="learnMore"
            >
              Learn More
            </md-button>
          </div>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"

export default {
  name: "PaystackConfiguration",
  components: { Widget },
  data() {
    return {
      title: "Paystack Payment Provider Setup",
    }
  },
  methods: {
    goToConfiguration() {
      this.$router.push("/paystack/overview")
    },
    learnMore() {
      window.open("https://paystack.com/docs", "_blank")
    },
  },
}
</script>

<style scoped>
.configuration-steps {
  display: flex;
  justify-content: space-around;
  flex-wrap: wrap;
  margin: 2rem 0;
  gap: 2rem;
}

.step {
  flex: 1;
  min-width: 250px;
  max-width: 300px;
  padding: 1.5rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background: #fafafa;
}

.step-icon {
  font-size: 3rem !important;
  color: #1976d2;
  margin-bottom: 1rem;
}

.step h4 {
  margin: 1rem 0;
  color: #333;
  font-weight: 600;
}

.step p {
  color: #666;
  line-height: 1.5;
  margin: 0;
}

.step a {
  color: #1976d2;
  text-decoration: none;
}

.step a:hover {
  text-decoration: underline;
}

.buttons-area {
  margin: 2rem auto;
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .configuration-steps {
    flex-direction: column;
    align-items: center;
  }
  
  .step {
    max-width: 90%;
  }
  
  .buttons-area {
    flex-direction: column;
    align-items: center;
  }
  
  .buttons-area .md-button {
    width: 200px;
  }
}
</style>
