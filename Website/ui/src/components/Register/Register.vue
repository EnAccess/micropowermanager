<template>
    <md-steppers :md-active-step.sync="activeStep" md-linear>
        <md-step class="stepper-step" id="firstStep" md-label="Company Information"
                 :md-done.sync="firstStep">
            <div class="exclamation">
                <div>
                    <div>
                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>{{ $tc('phrases.stepperLabels4', 1) }}</span>
                        </div>

                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>company info</span>
                        </div>
                    </div>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <md-button class="md-raised md-primary"
                                   v-if="!loadingNextStep"
                                   @click="nextStep('firstStep', 'secondStep')">
                            {{ $tc('words.continue') }}
                        </md-button>

                        <md-progress-bar md-mode="indeterminate" v-else/>
                    </div>
                </div>
            </div>
        </md-step>
        <md-step class="stepper-step" id="secondStep" md-label="Authorization" :md-done.sync="secondStep">
            <div class="exclamation">
                <div>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <span>plugin selection </span>
                    </div>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <md-field>
                            <label>{{ $tc('phrases.purchaseCode') }}</label>
                            <md-input></md-input>
                        </md-field>
                    </div>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <md-button class="md-raised md-primary"
                                   v-if="!loadingNextStep"
                                   @click="nextStep('secondStep', 'thirdStep')">
                            {{ $tc('words.continue') }}
                        </md-button>
                        <md-progress-bar md-mode="indeterminate" v-else/>
                    </div>
                </div>
            </div>
        </md-step>

        <md-step class="stepper-step" id="thirdStep" md-label="Complete" :md-done.sync="thirdStep">

            <div class="exclamation">
                <div>
                    <div class="md-layout-item md-size-100" id="logger-done-success"
                         v-if="1===1">
                        <span class="success-span">{{ $tc('words.successful') }}
                            <md-icon style="color: green">check</md-icon>
                        </span>

                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>User Creation</span>

                        </div>
                    </div>
                    <div class="md-layout-item md-size-100" id="logger-done-fail"
                         v-if="1===2">
                        <span class="failure-span">{{ $tc('phrases.somethingWentWrong') }}
                            <md-icon style="color: red">priority_high</md-icon>
                        </span>

                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>{{ $tc('phrases.stepperLabels3', 2) }}</span>
                        </div>
                    </div>

                    <div class="md-layout-item md-size-100">
                        <md-button class="md-raised md-primary">{{ $tc('words.done') }}</md-button>
                    </div>
                </div>
            </div>


        </md-step>
    </md-steppers>
</template>

<script>
import { RestrictionService } from '@/services/RestrictionService'
import { EventBus } from '@/shared/eventbus'

export default {
    name: 'Register',
    data () {
        return {
            loadingNextStep: false,
            activeStep: 'firstStep',
            firstStep: false,
            secondStep: false,
            thirdStep: false,

        }
    },
    methods: {
        async nextStep (id, index) {
            this[id] = true
            this.loadingNextStep = true

            if (id === 'firstStep' && index === 'secondStep') {

                if (index) {
                    this.activeStep = index
                }

            } else if (id === 'secondStep' && index === 'thirdStep') {

                if (index) {
                    this.activeStep = index
                }
            }
            this.loadingNextStep = false
        },
    },
}
</script>


<style scoped>
.stepper-step {
    text-align: center !important;
}

.md-stepper-content .md-active {
    text-align: center !important;
}

.success-span {
    font-size: large;
    font-weight: 700;
    color: green;
}

.failure-span {
    font-size: large;
    font-weight: 700;
    color: darkred;
}

.exclamation {
    margin: auto;
    align-items: center;
    display: inline-grid;
    text-align: center;

}

.watched-miniGrid-List {
    font-size: 11px;
    width: 15%;
    margin: auto;
    font-weight: bold;
}

.exclamation-div {
    margin-top: 2% !important;
}
</style>