// Test the frontend date calculation logic
function generateWeekOptions() {
  const weeks = [];
  const today = new Date();
  
  // Get the current week's Monday
  const currentWeekMonday = new Date(today);
  const dayOfWeek = today.getDay(); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
  const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Convert to Monday = 0
  currentWeekMonday.setDate(today.getDate() - daysToMonday);
  
  for (let i = 0; i < 5; i++) {
    const weekStart = new Date(currentWeekMonday);
    weekStart.setDate(currentWeekMonday.getDate() + (i * 7));
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    
    weeks.push({
      value: weekStart.toISOString().split('T')[0],
      label: `${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`,
      week_number: Math.ceil((weekStart.getTime() - new Date(weekStart.getFullYear(), 0, 1).getTime()) / (7 * 24 * 60 * 60 * 1000))
    });
  }
  
  return weeks;
}

// Test the function
console.log('Testing frontend date calculation...');
console.log('Today:', new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));

const weeks = generateWeekOptions();
weeks.forEach((week, index) => {
  console.log(`Week ${index + 1}: ${week.label} (value: ${week.value})`);
});

// Test specific date (Aug 4, 2025)
console.log('\nTesting specific date (Aug 4, 2025):');
const testDate = new Date(2025, 7, 4); // Month is 0-indexed, so 7 = August
console.log('Test date:', testDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));

const dayOfWeek = testDate.getDay();
const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
const weekStart = new Date(testDate);
weekStart.setDate(testDate.getDate() - daysToMonday);
const weekEnd = new Date(weekStart);
weekEnd.setDate(weekStart.getDate() + 6);

console.log('Week range:', `${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`);
