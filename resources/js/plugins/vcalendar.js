import VCalendar from 'v-calendar';
import 'v-calendar/style.css';

export default function setupVCalendar(app) {
  app.use(VCalendar, {
    componentPrefix: 'v',
  });
}
