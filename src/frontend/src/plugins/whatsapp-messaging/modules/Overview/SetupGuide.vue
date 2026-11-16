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
        <p>
          {{ $t("phrases.whatsappTemplatesGuideWebhookFields") }}
          <code>{{ webhookFields.join(", ") }}</code>
        </p>

        <h4>{{ $t("phrases.whatsappTemplatesTitle") }}</h4>
        <p>
          {{ $t("phrases.whatsappTemplatesGuideCreateIn") }}
          <a
            href="https://business.facebook.com/wa/manage/message-templates/"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ $t("phrases.whatsappTemplatesGuideWhatsappManager") }}
          </a>
        </p>
        <p>
          {{ $t("phrases.whatsappTemplatesGuideNotificationNames") }}
          <router-link to="/whatsapp-messaging/templates">
            {{ $t("phrases.whatsappTemplatesTitle") }}
          </router-link>
        </p>
        <ul>
          <li v-for="rule in templateRules" :key="rule">
            {{ $t(`phrases.whatsappTemplatesRule${rule}`) }}
          </li>
        </ul>

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
      // Template events only reach MPM when the app subscribes to their fields too.
      webhookFields: [
        "messages",
        "message_template_status_update",
        "template_category_update",
      ],
      // Meta's reasons for refusing a template or moving it to the more expensive Marketing category.
      templateRules: ["Utility", "NotOnlyVariable", "NotAtEdges", "OneLine"],
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
