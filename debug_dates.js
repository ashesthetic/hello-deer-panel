// Debug the exact date logic from the work schedule page
function debugDateLogic() {
  console.log('=== DEBUGGING DATE LOGIC ===');
  
  // Simulate the selected week value (e.g., "2025-08-04")
  const selectedWeek = "2025-08-04";
  console.log('Selected week value:', selectedWeek);
  
  // Test the OLD logic (what was causing issues)
  console.log('\n--- OLD LOGIC ---');
  const oldWeekStart = new Date(selectedWeek);
  console.log('Old weekStart:', oldWeekStart.toISOString());
  console.log('Old weekStart local:', oldWeekStart.toLocaleDateString());
  
  for (let i = 0; i < 7; i++) {
    const dayDate = new Date(oldWeekStart);
    dayDate.setDate(oldWeekStart.getDate() + i);
    console.log(`Day ${i + 1}: ${dayDate.toISOString().split('T')[0]} (${dayDate.toLocaleDateString()})`);
  }
  
  // Test the NEW logic (what we fixed)
  console.log('\n--- NEW LOGIC ---');
  const [year, month, day] = selectedWeek.split('-').map(Number);
  const newWeekStart = new Date(year, month - 1, day);
  console.log('New weekStart:', newWeekStart.toISOString());
  console.log('New weekStart local:', newWeekStart.toLocaleDateString());
  
  for (let i = 0; i < 7; i++) {
    const dayDate = new Date(newWeekStart);
    dayDate.setDate(newWeekStart.getDate() + i);
    console.log(`Day ${i + 1}: ${dayDate.toISOString().split('T')[0]} (${dayDate.toLocaleDateString()})`);
  }
  
  // Test what the backend API returns
  console.log('\n--- BACKEND API SIMULATION ---');
  const today = new Date();
  const currentWeekMonday = new Date(today);
  const dayOfWeek = today.getDay();
  const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
  currentWeekMonday.setDate(today.getDate() - daysToMonday);
  
  console.log('Today:', today.toLocaleDateString());
  console.log('Current week Monday:', currentWeekMonday.toLocaleDateString());
  
  // Find which week contains Aug 4, 2025
  for (let i = 0; i < 5; i++) {
    const weekStart = new Date(currentWeekMonday);
    weekStart.setDate(currentWeekMonday.getDate() + (i * 7));
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    
    const weekLabel = `${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
    const weekValue = weekStart.toISOString().split('T')[0];
    
    console.log(`Week ${i + 1}: ${weekLabel} (value: ${weekValue})`);
    
    // Check if this week contains Aug 4, 2025
    const aug4 = new Date(2025, 7, 4); // August 4, 2025
    if (weekStart <= aug4 && aug4 <= weekEnd) {
      console.log(`  *** This week contains Aug 4, 2025 ***`);
    }
  }
}

debugDateLogic();
