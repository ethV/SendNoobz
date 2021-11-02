# Audit each system in the domain for registry changes

param(
	[int]$hour = 3
	)

# For each system in domain, 
foreach($system in Get-AdComputer -Filter *){
    $computername = $system.name
    $currenttime = Get-Date
    $hoursago = $currenttime.AddHours(0-$hoursago)  # add negative hours here to go back in time

    # Query event log for event ID 4657, registry change
    Get-WinEvent -ComputerName $computername -FilterHashtable @{`
                            logname='Security';
                            id=4657;
                            StartTime=$hoursago} -ErrorAction SilentlyContinue `
                             | Format-List
}
