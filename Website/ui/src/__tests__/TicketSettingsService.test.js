jest.mock('../repositories/TicketSettingsRepository')
import { TicketSettingsService } from '../services/TicketSettingsService'
const ticketSettings = new TicketSettingsService()
const ticketSettingsProperties = ['id', 'name', 'apiToken', 'apiUrl', 'apiKey']

describe('TicketSettingsService #list',()=>{
    it('should get ticket settings data successfully', async ()=> {
        const data = await ticketSettings.list()
        expect(Object.keys(data).length).not.toEqual(0)
    })
    it('should get ticket settings data with correct properties', async ()=> {
        const data = await ticketSettings.list()
        Object.keys(data).forEach(function (item, index){
            expect(item).toEqual(ticketSettingsProperties[index])
        })
    })
    it('should not get null or undefined data', async ()=> {
        const data = await ticketSettings.list()
        Object.keys(data).forEach(function (item){
            expect(data[item]).not.toBeUndefined()
            expect(data[item]).not.toBeNull()
        })
    })
})

