<template>
    <div>
        <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field :class="{'md-invalid': errors.has($tc('words.title'))}">
                    <label>{{ $tc('words.title') }}</label>
                    <md-input :name="$tc('words.title')"
                              v-model="mainSettingsService.mainSettings.siteTitle"
                              :id="$tc('words.title')"
                              v-validate="'required|min:5'">

                    </md-input>
                    <span class="md-error">{{ errors.first($tc('words.title')) }}</span>
                </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field :class="{'md-invalid': errors.has('Company Name')}">
                    <label>Company Name</label>
                    <md-input name="Company Name"
                              id="Company Name"
                              v-model="mainSettingsService.mainSettings.companyName"
                              v-validate="'required|min:5'"
                    ></md-input>
                    <span class="md-error">{{ errors.first('Company Name') }}</span>
                </md-field>
            </div>
        </div>
        <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-30 md-small-size-100">
                <md-field>
                    <label for="currency">{{ $tc('words.currency') }}</label>
                    <md-select name="currency" id="currency" v-model="mainSettingsService.mainSettings.currency">
                        <md-option disabled>Select Currency</md-option>
                        <md-option v-for="(cur,index) in currencyListService.currencyList" :key="index"
                                   :value="cur.name">
                            {{
                                cur.name
                            }} - {{ cur.symbol }}
                        </md-option>
                    </md-select>
                </md-field>
            </div>
            <div class="md-layout-item md-size-40 md-small-size-100">
                <md-field>
                    <label for="country">Country</label>
                    <md-select name="country" id="country" v-model="mainSettingsService.mainSettings.country" md-dense>
                        <md-option disabled>Select Country</md-option>
                        <md-option v-for="(country,index) in countryListService.countryList" :key="index"
                                   :value="country">{{ country }}
                        </md-option>
                    </md-select>
                </md-field>
            </div>
            <div class="md-layout-item md-size-30 md-small-size-100">
                <md-field>
                    <label for="language">Language</label>
                    <md-select name="language" id="language" v-model="mainSettingsService.mainSettings.language"
                               md-dense>
                        <md-option disabled>Select Language</md-option>
                        <md-option v-for="(language,index) in  languagesService.languagesList" :key="index"
                                   :value="language">{{ language }}
                        </md-option>
                    </md-select>
                </md-field>
            </div>
        </div>
        <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field :class="{'md-invalid': errors.has('vat_energy')}">
                    <label for="vat_energy">VAT Energy</label>
                    <md-input
                        name="vat_energy"
                        id="vat_energy"
                        v-model="mainSettingsService.mainSettings.vatEnergy"
                        type="number"
                        maxlength="9"
                        v-validate="'required|decimal:2|max:4'"
                    ></md-input>
                </md-field>
                <span class="md-error">{{ errors.first('vat_energy') }}</span>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field :class="{'md-invalid': errors.has('vat_appliance')}">
                    <label for="vat_appliance">VAT Appliance</label>
                    <md-input
                        name="vat_appliance"
                        id="vat_appliance"
                        v-model="mainSettingsService.mainSettings.vatAppliance"
                        type="number"
                        maxlength="9"
                        v-validate="'required|decimal:2|max:4'"
                    ></md-input>
                </md-field>
                <span class="md-error">{{ errors.first('vat_appliance') }}</span>
            </div>
            <div class="md-layout md-alignment-bottom-right">
                <md-button class="md-primary md-dense md-raised" @click="updateMainSettings">Save</md-button>
            </div>

        </div>
        <md-progress-bar v-if="progress" md-mode="indeterminate"></md-progress-bar>
    </div>
</template>

<script>

import { CurrencyListService } from '@/services/CurrencyListService'
import { LanguagesService } from '@/services/LanguagesService'
import { CountryListService } from '@/services/CountryListService'
import { MainSettingsService } from '@/services/MainSettingsService'
import { EventBus } from '@/shared/eventbus'

export default {
    name: 'MainSettings',
    props: {
        mainSettings: {
            default: null
        }
    },
    data () {
        return {
            mainSettingsService: new MainSettingsService(),
            currencyListService: new CurrencyListService(),
            languagesService: new LanguagesService(),
            countryListService: new CountryListService(),
            currencyList: [],
            languagesList: [],
            countryList: [],
            progress: false,
        }

    },
    mounted () {
        if (!this.mainSettings) {
            this.mainSettingsService.mainSettings = this.$store.getters['settings/getMainSettings']
        } else {
            this.fetchMainSettings()
        }

        this.getCurrencyList()
        this.getLanguagesList()
        this.getCountryList()
    },
    methods: {
        fetchMainSettings () {
            this.mainSettingsService.mainSettings = this.mainSettings
        },
        async getCurrencyList () {
            try {
                await this.currencyListService.list()

            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async getCountryList () {
            try {
                await this.countryListService.list()
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async getLanguagesList () {
            try {
                await this.languagesService.list()
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },

        async updateMainSettings () {
            this.progress = true
            let validator = await this.$validator.validateAll()
            if (!validator) {
                return
            }
            try {
                await this.mainSettingsService.update()
                this.$store.dispatch('settings/setMainSettings', this.mainSettingsService.mainSettings).then(() => {
                    this.updateStoreStates(this.mainSettingsService.mainSettings)
                }).catch((err) => {
                    console.log(err)
                })
                this.alertNotify('success', 'Updated Successfully')
                EventBus.$emit('Settings')
            } catch (e) {
                this.alertNotify('error', e.message)
            }
            this.progress = false
        },

        updateStoreStates (mainSettings) {
            document.title = mainSettings.siteTitle
            this.$i18n.locale = mainSettings.language

        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    }
}
</script>

<style scoped>

</style>
