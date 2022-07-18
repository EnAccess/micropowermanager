import RepositoryFactory from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class MainSettingsService {

    constructor () {
        this.repository = RepositoryFactory.get('mainSettings')
        this.mainSettings = {
            siteTitle: null,
            companyName: null,
            currency: null,
            country: null,
            language: null,
            vatEnergy: null,
            vatAppliance: null,
        }
    }

    fromJson (mainSettings) {
        this.mainSettings = {
            id: mainSettings.id,
            siteTitle: mainSettings.site_title,
            companyName: mainSettings.company_name,
            currency: mainSettings.currency,
            country: mainSettings.country,
            language: mainSettings.language,
            vatEnergy: mainSettings.vat_energy,
            vatAppliance: mainSettings.vat_appliance,
        }
        return this.mainSettings
    }

    async list () {
        try {
            let response = await this.repository.list()
            if (response.status === 200) {
                this.fromJson(response.data.data)
                return this.mainSettings
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            return new ErrorHandler(e.response.data.message, 'http')
        }
    }

    async update () {
        try {
            let mainSettingsPm = {
                id: this.mainSettings.id,
                site_title: this.mainSettings.siteTitle,
                company_name: this.mainSettings.companyName,
                currency: this.mainSettings.currency,
                country: this.mainSettings.country,
                language: this.mainSettings.language,
                vat_energy: this.mainSettings.vatEnergy,
                vat_appliance: this.mainSettings.vatAppliance,
            }
            let response = await this.repository.update(mainSettingsPm.id,
                mainSettingsPm)
            if (response.status === 200) {
                this.fromJson(response.data.data)
                return this.mainSettings
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            return new ErrorHandler(e.response.data.message, 'http')
        }
    }
}
