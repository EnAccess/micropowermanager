<template>
    <div class="register">
        <div class="header">
            <h1 class="title">MicroPowerManager</h1>
            <div class="title-divider">&nbsp;</div>
        </div>
        <div class="content">
            <md-steppers class="register-stepper" :md-active-step.sync="activeStep" md-linear>
                <md-step class="stepper-step" id="Company-Form" md-label="Company Form"
                         :md-done.sync="firstStep">
                    <div class="exclamation">
                        <div>
                            <div class="md-layout-item md-size-100 exclamation-div">
                                <h2 class="stepper-title">Please fill your company specific information's</h2>
                            </div>
                            <div class="md-layout-item md-size-100">
                                <form class="md-layout md-gutter" data-vv-scope="Company-Form">
                                    <div class="md-layout-item  md-size-50 md-small-size-100">
                                        <md-field
                                            :class="{'md-invalid': errors.has('Company-Form.'+ $tc('words.name'))}">
                                            <label for="name">{{ $tc('words.name') }}</label>
                                            <md-input
                                                type="text"
                                                :name="$tc('words.name')"
                                                :id="$tc('words.name')"
                                                v-model="companyForm.name"
                                                v-validate="'required|min:3|max:50'"
                                            />
                                            <span class="md-error">{{
                                                    errors.first('Company-Form.' + $tc('words.name'))
                                                }}</span>
                                        </md-field>
                                    </div>
                                    <div class="md-layout-item md-size-50 md-small-size-100">
                                        <template>
                                            <vue-tel-input
                                                :validCharactersOnly="true"
                                                mode="international"
                                                invalidMsg="invalid phone number"
                                                :disabledFetchingCountry="false"
                                                :disabledFormatting="false"
                                                placeholder="Enter a phone number"
                                                :required="true"
                                                :preferredCountries="['TZ', 'CM', 'KE', 'NG', 'UG']"
                                                autocomplete="off"
                                                name="telephone"
                                                enabledCountryCode="true"
                                                v-model="companyForm.phone"
                                                @validate="validatePhone"
                                            ></vue-tel-input>
                                            <span v-if="!phone.valid && firstStepClicked" style="color:red"
                                                  class="md-error">invalid phone number</span>
                                        </template>

                                    </div>
                                    <div class="md-layout-item  md-size-50 md-small-size-100">
                                        <md-field
                                            :class="{'md-invalid': errors.has('Company-Form.'+ $tc('words.address'))}">
                                            <label for="address">{{ $tc('words.address') }}</label>
                                            <md-input
                                                type="text"
                                                :name="$tc('words.address')"
                                                :id="$tc('words.address')"
                                                v-validate="'required'"
                                                v-model="companyForm.address"
                                            />
                                            <span class="md-error">{{
                                                    errors.first('Company-Form.' + $tc('words.address'))
                                                }}</span>
                                        </md-field>
                                    </div>
                                    <div class="md-layout-item  md-size-50 md-small-size-100">
                                        <md-field
                                            :class="{'md-invalid': errors.has('Company-Form.'+$tc('words.email'))}">
                                            <label for="email">{{ $tc('words.email') }}</label>
                                            <md-input
                                                type="email"
                                                :name="$tc('words.email')"
                                                :id="$tc('words.email')"
                                                autocomplete="email"
                                                v-validate="'required|email'"
                                                v-model="companyForm.email"
                                            />
                                            <span class="md-error">{{
                                                    errors.first('Company-Form.' + $tc('words.email'))
                                                }}</span>
                                        </md-field>
                                    </div>

                                </form>

                            </div>
                            <div class="md-layout-item md-size-100 exclamation-div">
                                <md-button class="md-raised md-primary"
                                           v-if="!loadingNextStep"
                                           @click="nextStep('Company-Form', 'Plugins')">
                                    {{ $tc('words.continue') }}
                                </md-button>
                                <md-progress-bar md-mode="indeterminate" v-else/>
                            </div>
                        </div>
                    </div>
                </md-step>
                <md-step class="stepper-step" id="Plugins" md-label="Plugin Selection" :md-done.sync="secondStep">
                    <div class="exclamation">
                        <div>
                            <div class="md-layout-item md-size-100 exclamation-div">
                                <h2 class="stepper-title"> Please select the plugin(s) you would like to use with your
                                    MicroPowerManager</h2>
                            </div>
                            <div class="md-layout md-gutter">
                                <div v-for="plugin in mpmPluginsService.list"
                                     class="box md-layout-item  md-size-25 md-small-size-50">
                                    <div class="header-text">{{ plugin.name }}
                                        <input type="checkbox" v-model="plugin.checked"/>
                                    </div>
                                    <small class="sub-text" v-html="plugin.description">{{ plugin.description }}</small>

                                </div>
                            </div>
                            <div class="md-layout-item md-size-100 exclamation-div">
                                <md-button class="md-raised md-primary"
                                           v-if="!loadingNextStep"
                                           @click="nextStep('Plugins', 'Create-Form')">
                                    {{ $tc('words.continue') }}
                                </md-button>
                                <md-progress-bar md-mode="indeterminate" v-else/>
                            </div>
                        </div>
                    </div>
                </md-step>
                <md-step class="stepper-step" id="Create-Form" md-label="User Creation" :md-done.sync="secondStep">
                    <div class="exclamation">
                        <div>
                            <div class="md-layout-item md-size-100 exclamation-div">
                                <h2 class="stepper-title"> Please create a user for MicroPowerManager</h2>
                            </div>
                            <div class="md-layout-item md-size-100">
                                <form data-vv-scope="Create-Form" class="md-layout md-gutter">

                                    <div class="md-layout-item md-size-50 md-small-size-100">
                                        <md-field
                                            :class="{'md-invalid': errors.has('Create-Form.' + $tc('words.name'))}">
                                            <label>{{ $tc('words.name') }}</label>
                                            <md-input
                                                v-model="user.name"
                                                v-validate="'required|min:2|max:20'"
                                                :name="$tc('words.name')"
                                                id="name"
                                            />
                                            <md-icon>create</md-icon>
                                            <span class="md-error">{{
                                                    errors.first('Create-Form.' + $tc('words.name'))
                                                }}</span>
                                        </md-field>
                                    </div>
                                    <div class="md-layout-item md-size-50 md-small-size-100">
                                        <md-field
                                            :class="{'md-invalid': errors.has('Create-Form.' +$tc('words.email'))}">
                                            <label>{{ $tc('words.email') }}</label>
                                            <md-input
                                                type="text"
                                                :name="$tc('words.email')"
                                                id="email"
                                                v-model="user.email"
                                                v-validate="'required|email'"
                                            />
                                            <md-icon>email</md-icon>
                                            <span class="md-error">{{
                                                    errors.first('Create-Form.' + $tc('words.email'))
                                                }}</span>
                                        </md-field>
                                    </div>
                                    <div class="md-layout-item md-size-50 md-small-size-100">
                                        <md-field
                                            :class="{'md-invalid': errors.has('Create-Form.' +$tc('words.password'))}">
                                            <label for="password">{{ $tc('words.password') }}</label>
                                            <md-input
                                                type="password"
                                                :name="$tc('words.password')"
                                                id="password"
                                                v-validate="'required|min:3|max:15'"
                                                v-model="user.password"
                                                ref="passwordRef"
                                            />

                                            <span class="md-error">{{
                                                    errors.first('Create-Form.' + $tc('words.password'))
                                                }}</span>
                                        </md-field>
                                    </div>
                                    <div class="md-layout-item md-size-50 md-small-size-100">
                                        <md-field
                                            :class="{'md-invalid': errors.has('Create-Form.' +$tc('phrases.confirmPassword'))}">
                                            <label for="confirmPassword">{{ $tc('phrases.confirmPassword') }}</label>
                                            <md-input
                                                type="password"
                                                :name="$tc('phrases.confirmPassword')"
                                                id="confirmPassword"
                                                v-model="user.confirmPassword"
                                                v-validate="'required|confirmed:passwordRef|min:3|max:15'"
                                            />
                                            <span class="md-error">{{
                                                    errors.first('Create-Form.' + $tc('phrases.confirmPassword'))
                                                }}</span>
                                        </md-field>
                                    </div>


                                </form>
                            </div>
                            <div class="md-layout-item md-size-100 exclamation-div">
                                <md-button class="md-raised md-primary"
                                           v-if="!loadingNextStep"
                                           @click="nextStep('Create-Form', 'Complete')">
                                    {{ $tc('words.continue') }}
                                </md-button>
                                <md-progress-bar md-mode="indeterminate" v-else/>
                            </div>
                        </div>
                    </div>
                </md-step>
                <md-step class="stepper-step" id="Complete" md-label="Complete" :md-done.sync="thirdStep">

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
        </div>
    </div>

</template>

<script>


import { MpmPluginService } from '@/services/MpmPluginService'

export default {
    name: 'Register',
    data () {
        return {
            mpmPluginsService: new MpmPluginService(),
            loadingNextStep: false,
            activeStep: 'Company-Form',
            firstStepClicked: false,
            firstStep: false,
            secondStep: false,
            thirdStep: false,
            phone: {
                valid: true
            },
            companyForm: {
                name: '',
                address: '',
                phone: '',
                email: '',
                country: null,
            },
            user: {
                name: '',
                email: '',
                password: '',
                confirmPassword: ''
            }
        }
    },
    mounted () {
        this.mpmPluginsService.getMpmPlugins()
    },
    methods: {
        async nextStep (id, index) {
            this.loadingNextStep = true

            if (id === 'Company-Form' && index === 'Plugins') {
                this.firstStepClicked = true
                const validation = await this.$validator.validateAll('Company-Form')
                if (!validation || !this.phone.valid) {
                    this.loadingNextStep = false
                    return
                }
                if (index) {
                    this.activeStep = index
                }

            } else if (id === 'Plugins' && index === 'Create-Form') {

                if (index) {
                    this.activeStep = index
                }
            } else if (id === 'Create-Form' && index === 'Complete') {
                const validation = await this.$validator.validateAll(id)
                if (!validation) {
                    this.loadingNextStep = false
                    return
                }
                this.register()
                if (index) {
                    this.activeStep = index
                }
            }
            this.loadingNextStep = false
        },

        validatePhone (phone) {
            this.phone = phone
        },
        register () {
            let asd = this.companyForm
            let basd = this.phone
            let csd = this.mpmPluginsService.list
            debugger
        }
    },

}
</script>


<style scoped lang="scss">

.register {
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 1024px;
    margin: auto;
}

.content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    text-align: center;
}

.header {
    margin-top: 14rem;
    width: 100%;
}

.register-stepper {
    width: 100%;

}

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


.exclamation-div {
    margin-top: 2% !important;
}

.vue-tel-input {

    display: flex;
    border: 0px solid #bbb;
    text-align: left;
    border-bottom: 1px solid #bbb;
    margin-top: 1rem;
}

.box {
    border-radius: 5px;
    padding: 1.3vw;
    margin-top: 1vh;
    box-shadow: 0 1px 5px -2px rgb(53 53 53 / 30%), 0 0px 4px 0 rgb(0 0 0 / 12%), 0 0px 0px -5px #8e8e8e
}

.header-text {
    color: rgb(148, 148, 148);
    margin-top: 0px;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: bold;
}

.sub-text {
    font-weight: 400;
    font-size: 0.7rem;
}

.stepper-title {
    text-align: center !important;
    font-size: large !important;
    padding: 1rem 1rem 0 1rem;
    margin-bottom: 3rem !important;
    font-weight: bolder !important;
}

.md-steppers-navigation {
    box-shadow: none;
    display: flex;
    border-bottom: 1px solid #bbb;
}
</style>