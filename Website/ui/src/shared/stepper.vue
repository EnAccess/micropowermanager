<template>
    <md-steppers :md-active-step.sync="activeStep" md-linear>
        <md-step
            class="stepper-step"
            id="firstStep"
            md-label="Activate Date-logger"
            :md-done.sync="firstStep"
        >
            <div class="exclamation">
                <div>
                    <div id="logger" v-if="purchasingType === 'logger'">
                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>{{ $tc('phrases.stepperLabels', 1) }}</span>
                        </div>
                        <div class="md-layout-item md-size-100 exclamation-div">
                            <div>
                                <ul class="watched-miniGrid-List">
                                    <li
                                        v-for="(
                                            miniGrid, key
                                        ) in watchingMiniGrids"
                                        :key="key"
                                    >
                                        {{ miniGrid.name }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>{{ $tc('phrases.stepperLabels', 2) }}</span>
                        </div>
                    </div>
                    <div
                        id="maintenance"
                        v-if="purchasingType === 'maintenance'"
                    >
                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>{{ $tc('phrases.stepperLabels4', 1) }}</span>
                        </div>

                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>{{ $tc('phrases.stepperLabels4', 2) }}</span>
                        </div>
                    </div>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <md-button
                            class="md-raised md-primary"
                            v-if="!loadingNextStep"
                            @click="nextStep('firstStep', 'secondStep')"
                        >
                            {{ $tc('words.continue') }}
                        </md-button>

                        <md-progress-bar md-mode="indeterminate" v-else />
                    </div>
                </div>
            </div>
        </md-step>
        <md-step
            class="stepper-step"
            id="secondStep"
            md-label="Authorization"
            :md-done.sync="secondStep"
        >
            <div class="exclamation">
                <div>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <span>{{ $tc('phrases.stepperLabels2', 1) }}</span>
                    </div>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <md-field>
                            <label>{{ $tc('phrases.purchaseCode') }}</label>
                            <md-input v-model="purchaseCode"></md-input>
                        </md-field>
                    </div>
                    <div class="md-layout-item md-size-100 exclamation-div">
                        <md-button
                            class="md-raised md-primary"
                            v-if="!loadingNextStep"
                            @click="nextStep('secondStep', 'thirdStep')"
                        >
                            {{ $tc('words.continue') }}
                        </md-button>
                        <md-progress-bar md-mode="indeterminate" v-else />
                    </div>
                </div>
            </div>
        </md-step>

        <md-step
            class="stepper-step"
            id="thirdStep"
            md-label="Complete"
            :md-done.sync="thirdStep"
        >
            <div class="exclamation">
                <div>
                    <div
                        class="md-layout-item md-size-100"
                        id="logger-done-success"
                        v-if="PaymentProcess === true"
                    >
                        <span class="success-span">
                            {{ $tc('words.successful') }}
                            <md-icon style="color: green">check</md-icon>
                        </span>

                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span v-if="purchasingType === 'logger'">
                                {{ $tc('phrases.stepperLabels2', 2) }}
                            </span>
                            <span v-if="purchasingType === 'maintenance'">
                                {{ $tc('phrases.stepperLabels3', 1) }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="md-layout-item md-size-100"
                        id="logger-done-fail"
                        v-if="PaymentProcess === false"
                    >
                        <span class="failure-span">
                            {{ $tc('phrases.somethingWentWrong') }}
                            <md-icon style="color: red">priority_high</md-icon>
                        </span>

                        <div class="md-layout-item md-size-100 exclamation-div">
                            <span>{{ $tc('phrases.stepperLabels3', 2) }}</span>
                        </div>
                    </div>

                    <div class="md-layout-item md-size-100">
                        <md-button
                            class="md-raised md-primary"
                            @click="closeStepper()"
                        >
                            {{ $tc('words.done') }}
                        </md-button>
                    </div>
                </div>
            </div>
        </md-step>
    </md-steppers>
</template>

<script>
import { RestrictionService } from '@/services/RestrictionService'
import { EventBus } from './eventbus'

export default {
    name: 'Stepper',

    props: {
        purchasingType: String,
        watchingMiniGrids: Array,
    },
    data() {
        return {
            loadingNextStep: false,
            restrictionService: new RestrictionService(),
            activeStep: 'firstStep',
            firstStep: false,
            secondStep: false,
            thirdStep: false,
            purchaseCode: '',
            PaymentProcess: false,
        }
    },
    methods: {
        async nextStep(id, index) {
            this[id] = true
            this.loadingNextStep = true

            if (id === 'firstStep' && index === 'secondStep') {
                if (this.purchasingType === 'logger') {
                    window.open(
                        'https://micropowermanager.com/logger',
                        '_blank',
                    )
                } else {
                    window.open(
                        'https://micropowermanager.com/maintainer',
                        '_blank',
                    )
                }
                if (index) {
                    this.activeStep = index
                }
            } else if (id === 'secondStep' && index === 'thirdStep') {
                let email = this.$store.state.admin.email
                try {
                    let data = await this.restrictionService.sendPurchaseCode(
                        this.purchaseCode,
                        email,
                    )
                    let productCode = data.display_items[0].custom.description
                    let type =
                        this.purchasingType === 'logger'
                            ? 'mini-grid'
                            : 'maintenance'

                    let codeIsValid =
                        await this.restrictionService.purchaseCodeIsValid(
                            this.purchaseCode,
                            productCode,
                            type,
                        )
                    if (codeIsValid) {
                        this.PaymentProcess = true
                    }
                } catch (e) {
                    this.PaymentProcess = false
                }
                if (index) {
                    this.activeStep = index
                }
            }

            this.loadingNextStep = false
        },

        closeStepper() {
            EventBus.$emit('closeModal', this.PaymentProcess)
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
