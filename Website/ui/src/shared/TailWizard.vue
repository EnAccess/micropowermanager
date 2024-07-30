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
import { EventBus } from "@/shared/eventbus"
import { RegistrationTailService } from "@/services/RegistrationTailService"

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
    if (this.tail && this.tail.length) {
      for (const tailObj of this.tail) {
        if ("tag" in tailObj) {
          EventBus.$on(tailObj.tag, () => {
            this.updateRegistrationTail(tailObj.tag)
          })
        }
      }

      this.activeStep = this.tail[0].tag
    }
  },
  data() {
    return {
      loadingNextStep: false,
      activeStep: "",
      wizardIsVisible: false,
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
        const tailId = this.$store.getters["registrationTail/getTail"].id
        await this.registrationTailService.updateRegistrationTail(
          tailId,
          tag,
          this.tail,
        )
        const step = tag
        let stepIndex = 0
        for (let i = 0; i < this.tail.length; i++) {
          for (let [k, v] of Object.entries(this.tail[i])) {
            if (k === "tag" && v === step) {
              stepIndex = i
              break
            }
          }
        }
        const nextStep = this.tail[stepIndex + 1]
        this.$store.commit(
          "registrationTail/SET_REGISTRATION_TAIL",
          this.registrationTailService.registrationTail,
        )
        this.nextStep(step, nextStep)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped lang="scss">
.tail-stepper {
  width: 940px;
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
</style>
