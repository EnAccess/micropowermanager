<template>
  <div>
    <md-dialog
      :md-active.sync="wizardIsVisible"
      :md-click-outside-to-close="false"
    >
      <md-dialog-content>
        <md-steppers
          class="tail-stepper"
          md-linear
          :md-active-step.sync="activeStep"
        >
          <md-step
            class="stepper-step"
            v-for="(tailObj, index) in tail"
            :key="index"
            :id="tailObj.tag"
            :md-label="tailObj.tag"
          >
            <div class="exclamation">
              <div class="md-layout-item md-size-100">
                <component :is="tailObj.component" />
              </div>
              <div class="md-layout-item md-size-100 exclamation-div">
                <md-button
                  class="md-primary md-block"
                  @click="nextStep(tailObj.tag, tail[index + 1])"
                >
                  Do this later.
                </md-button>
              </div>
            </div>
          </md-step>
        </md-steppers>
      </md-dialog-content>
    </md-dialog>
  </div>
</template>

<script>
import { RegistrationTailService } from "@/services/RegistrationTailService.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "TailWizard",
  props: {
    showWizard: {
      type: Boolean,
      required: true,
    },
    tail: {
      type: Array,
      required: true,
    },
  },
  mounted() {
    this.wizardIsVisible = this.showWizard
    if (!this.tail || !this.tail.length) {
      return
    }
    for (const tailObj of this.tail) {
      if (!("tag" in tailObj)) {
        continue
      }
      const handler = () => this.updateRegistrationTail(tailObj.tag)
      this.tailListeners.push({ tag: tailObj.tag, handler })
      EventBus.$on(tailObj.tag, handler)
    }
    this.activeStep = this.tail[0].tag
  },
  beforeDestroy() {
    for (const { tag, handler } of this.tailListeners) {
      EventBus.$off(tag, handler)
    }
  },
  data() {
    return {
      loadingNextStep: false,
      activeStep: "",
      wizardIsVisible: false,
      tailListeners: [],
      registrationTailService: new RegistrationTailService(),
    }
  },
  methods: {
    nextStep(step, nextStep) {
      if (nextStep) {
        this.activeStep = nextStep.tag
      } else {
        this.activeStep = null
        this.wizardIsVisible = false
        this.$store.commit("registrationTail/SET_IS_WIZARD_SHOWN", true)
      }
    },

    async updateRegistrationTail(tag) {
      this.loadingNextStep = true
      try {
        const stepIndex = this.tail.findIndex((step) => step.tag === tag)
        const step = this.tail[stepIndex]
        if (step) {
          await this.registrationTailService.adjustStep(step.id)
        }
        this.nextStep(tag, this.tail[stepIndex + 1])
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loadingNextStep = false
      }
    },
  },
}
</script>

<style scoped lang="scss">
.tail-stepper {
  width: 940px;
  max-width: 100%;
}

.stepper-step {
  text-align: center !important;
}

.md-stepper-content .md-active {
  text-align: center !important;
}

.exclamation {
  margin: auto;
  align-items: center;
  display: inline-grid;
  text-align: center;
}

.md-dialog {
  z-index: 10;
}

@media (max-width: 768px) {
  .tail-stepper {
    width: 100%;
  }

  .md-dialog {
    max-width: 100%;
    margin: 0.5rem;
  }
}
</style>
