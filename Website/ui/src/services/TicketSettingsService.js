import { ErrorHandler } from '@/Helpers/ErrorHander'
import TicketSettingsRepository from '@/repositories/TicketSettingsRepository'

export class TicketSettingsService {
    constructor() {
        this.repository = TicketSettingsRepository
        this.ticketSettings = {
            name: null,
            api_token: null,
            api_url: null,
            api_key: null,
        }
    }

    fromJson(ticketSettings) {
        this.ticketSettings = {
            id: ticketSettings.id,
            name: ticketSettings.name,
            apiToken: ticketSettings.api_token,
            apiUrl: ticketSettings.api_url,
            apiKey: ticketSettings.api_key,
        }

        return this.ticketSettings
    }

    async list() {
        try {
            let response = await this.repository.list()
            if (response.status === 200) {
                this.fromJson(response.data.data[0])
                return this.ticketSettings
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let erorMessage = e.response.data.message
            return new ErrorHandler(erorMessage, 'http')
        }
    }

    async update() {
        try {
            let ticketSettingPm = {
                id: this.ticketSettings.id,
                name: this.ticketSettings.name,
                api_token: this.ticketSettings.apiToken,
                api_url: this.ticketSettings.apiUrl,
                api_key: this.ticketSettings.apiKey,
            }
            let response = await this.repository.update(
                ticketSettingPm.id,
                ticketSettingPm,
            )
            if (response.status === 200) {
                this.fromJson(response.data.data)

                return this.ticketSettings
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let erorMessage = e.response.data.message
            return new ErrorHandler(erorMessage, 'http')
        }
    }
}
