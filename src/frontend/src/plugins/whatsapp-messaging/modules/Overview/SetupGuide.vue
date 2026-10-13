<template>
  <md-card>
    <md-card-header>
      <div class="md-title">{{ $t("phrases.whatsappSetupGuide") }}</div>
    </md-card-header>
    <md-card-content class="guide">
      <ul class="step-list">
        <li
          v-for="(step, index) in steps"
          :key="step.title"
          :class="{ active: index === activeIndex }"
          @click="activeIndex = index"
        >
          {{ $t(step.title) }}
        </li>
      </ul>

      <div class="step-content">
        <h3>{{ $t(activeStep.title) }}</h3>
        <md-button
          class="md-primary md-raised"
          :href="activeStep.url"
          target="_blank"
          rel="noopener"
        >
          <md-icon>open_in_new</md-icon>
          {{ activeStep.url.replace("https://", "") }}
        </md-button>
        <template v-if="activeStep.showsWebhook">
          <h4>{{ $t("phrases.whatsappGuideWebhook") }}</h4>
          <copy-field
            :label="$t('phrases.whatsappWebhookUrl')"
            :value="credential.webhookUrl"
          />
          <copy-field
            :label="$t('phrases.whatsappWebhookVerifyToken')"
            :value="credential.webhookVerifyToken"
          />
        </template>
      </div>
    </md-card-content>
    <md-card-actions>
      <md-button :disabled="activeIndex === 0" @click="activeIndex--">
        {{ $tc("words.previous") }}
      </md-button>
      <md-button
        class="md-primary"
        :disabled="activeIndex === steps.length - 1"
        @click="activeIndex++"
      >
        {{ $tc("words.next") }}
      </md-button>
    </md-card-actions>
  </md-card>
</template>

<script>
import CopyField from "./CopyField.vue"

const steps = [
  {
    title: "phrases.whatsappGuideBusinessPortfolio",
    url: "https://business.facebook.com",
  },
  {
    title: "phrases.whatsappGuideMetaApp",
    url: "https://developers.facebook.com/apps",
    showsWebhook: true,
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
      activeIndex: 0,
    }
  },
  computed: {
    activeStep() {
      return this.steps[this.activeIndex]
    },
  },
}
</script>

<style lang="scss" scoped>
.md-card {
  height: 100%;
}

.guide {
  display: flex;
  gap: 2rem;
}

.step-list {
  flex: 0 0 auto;
  margin: 0;
  padding: 0;
  list-style: none;
  border-left: 1px solid rgba(0, 0, 0, 0.12);

  li {
    margin-left: -1px;
    padding: 0.5rem 1rem;
    border-left: 3px solid transparent;
    color: rgba(0, 0, 0, 0.54);
    font-size: 1rem;
    cursor: pointer;

    &.active {
      border-left-color: $brand-primary;
      color: $brand-primary;
      font-weight: 500;
    }
  }
}

.step-content {
  flex: 1;
  min-width: 0;

  h3 {
    margin-top: 0;
  }

  h4 {
    margin: 1.5rem 0 0;
  }
}

@media (max-width: 600px) {
  .guide {
    flex-direction: column;
  }
}
</style>
