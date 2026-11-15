<template>
  <md-card>
    <md-card-content>
      <div class="info-box">
        <h3>{{ $t("phrases.whatsappSetupGuide") }}</h3>
        <ol>
          <li v-for="step in steps" :key="step.text">
            {{ $t(step.text) }}
            <a
              v-if="step.url"
              :href="step.url"
              target="_blank"
              rel="noopener noreferrer"
            >
              ({{ step.url.replace("https://", "") }})
            </a>
          </li>
        </ol>

        <h4>{{ $t("phrases.whatsappGuideWebhook") }}</h4>
        <copy-field
          :label="$t('phrases.whatsappWebhookUrl')"
          :value="credential.webhookUrl"
        />
        <copy-field
          :label="$t('phrases.whatsappWebhookVerifyToken')"
          :value="credential.webhookVerifyToken"
        />

        <div class="pricing-info">
          <p>
            <strong>{{ $t("phrases.whatsappPricing") }}</strong>
            <a
              href="https://sleekflow.io/blog/whatsapp-business-price"
              target="_blank"
              rel="noopener noreferrer"
            >
              {{ $t("phrases.whatsappPricingLink") }}
            </a>
          </p>
        </div>
      </div>
    </md-card-content>
  </md-card>
</template>

<script>
import CopyField from "./CopyField.vue"

const steps = [
  {
    text: "phrases.whatsappGuideBusinessPortfolio",
    url: "https://business.facebook.com",
  },
  {
    text: "phrases.whatsappGuideMetaApp",
    url: "https://developers.facebook.com/apps",
  },
]

export default {
  name: "SetupGuide",
  components: { CopyField },
  props: {
    credential: { type: Object, required: true },
  },
  data() {
    return {
      steps,
    }
  },
}
</script>

<style lang="scss" scoped>
.md-card {
  height: 100%;
}

.info-box {
  padding: 1.5rem;
  background-color: #f8f9fa;
  border-radius: 4px;

  h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.1rem;
    margin-bottom: 1rem;
  }

  h4 {
    margin: 1.5rem 0 0;
    color: #333;
  }

  ol {
    padding-left: 1.5rem;
    margin-bottom: 1rem;

    li {
      margin-bottom: 0.5rem;
      line-height: 1.5;
    }
  }

  a {
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }

  .pricing-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #ddd;

    p {
      margin: 0;
      color: #666;
    }
  }
}
</style>
